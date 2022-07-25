<?php

namespace DexCrawler\Service;

use ArrayIterator;
use DexCrawler\Entity\Maker;
use DexCrawler\Factory;
use DexCrawler\Reader\RedisReader;
use DexCrawler\ValueObjects\Address;
use DexCrawler\ValueObjects\Holders;
use DexCrawler\ValueObjects\Name;
use DexCrawler\Writer\RedisWriter;
use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\UnexpectedTagNameException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use InvalidArgumentException;
use Symfony\Component\Panther\Client as PantherClient;


class Crawler
{
    private PantherClient $client;

    public array $namesToFindDrop = [];
    public static $hook = 'https://hooks.slack.com/services/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';
    private const URL = 'https://bscscan.com/dextracker?filter=1';

    private const URL_TOKEN = 'https://bscscan.com/token/';

    private const INDEX_OF_SHOWN_ROWS = 3;

    public static $valuesForCrawler = [
        [10, 50],
        [20, 50],
        [50, 110],
    ];

    private const SCRIPT = <<<EOF
var selectedA = document.querySelector('#selectDex');
var divWithDexList = document.querySelector('#selectDexButton');
var select = document.getElementById('ContentPlaceHolder1_ddlRecordsPerPage');

selectedA.click();
divWithDexList.querySelector('#selectDexButton > a:nth-child(5) > img').click();
EOF;


    public function invoke(): void
    {
        try {
            echo "Start crawling " . date("F j, Y, g:i:s a") . PHP_EOL;
            $this->getCrawlerForWebsite(self::URL);
            $this->client->executeScript(self::SCRIPT);
            $this->changeOnWebsiteToShowMoreRecords();
            sleep(1);
            $this->scrappingData();
            $this->client->restart();
        } catch (Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
            $this->client->restart();
        } finally {
            $this->client->close();
            $this->client->quit();
        }
    }

    private function getContent(): ?ArrayIterator
    {

        try {
            $list = $this->client->getCrawler()
                ->filter('#content > div.container.space-bottom-2 > div > div.card-body')
                ->filter('table.table-hover > tbody')
                ->children()
                ->getIterator();

        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
        return $list;
    }

    private function assignMakerAndTakerFrom(
        ?ArrayIterator $content
    ): array
    {
        $tokensWithoutHolder = [];
        foreach ($content as $webElement) {
            try {
                assert($webElement instanceof RemoteWebElement);

                $name = $webElement
                    ->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))
                    ->getText();

                $this->namesToFindDrop[] = $name;

                $nameOfMaker = Name::fromString($name);
                $maker = RedisReader::findKey($nameOfMaker->asString());

                if ($maker) {
                    continue;
                }

                $information = $webElement
                    ->findElement(WebDriverBy::cssSelector('tr > td:nth-child(5)'))
                    ->getText();
                $service = Information::fromString($information);
                $tokenNameOfTaker = $service->getToken();
                $price = $service->getPrice();
                $taker = Factory::createTaker($tokenNameOfTaker, $price);
                $currentTimestamp = time();

                $address = $webElement
                    ->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))
                    ->getAttribute('href');

                $makerAddress = Address::fromString($address);

                $token = Factory::createMaker(
                    $nameOfMaker,
                    $makerAddress,
                    $taker,
                    $currentTimestamp
                );
                $tokensWithoutHolder[] = $token;
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        return $tokensWithoutHolder;
    }

    public function proveIfIsWorthToBuyIt($makersWithoutHolders): ?array
    {

        if ($makersWithoutHolders !== null) {
            $tokensForAlert = [];

            foreach ($makersWithoutHolders as $maker) {

                assert($maker instanceof Maker);

                $url = self::URL_TOKEN . $maker->getAddress()->asString();

                $this->getCrawlerForWebsite($url);

                $holdersString = $this->client->getCrawler()
                    ->filter('#ContentPlaceHolder1_tr_tokenHolders > div > div.col-md-8 > div > div')
                    ->getText();

                try {
                    $holdersNumber = (int)str_replace(',', "", explode(' ', $holdersString)[0]);
                    $holders = Holders::fromInt($holdersNumber);
                    $maker->setHolders($holders);

                    $tokensForAlert[] = $maker;
                } catch (Exception) {
                    continue;
                }
            }
            return $tokensForAlert;

        } else {
            return null;
        }
    }

    private function scrappingData(): void
    {
        $tokensWithoutHolders = [];
        echo 'Start getting content ' . date("F j, Y, g:i:s a") . PHP_EOL;
        for ($i = 0; $i < self::$valuesForCrawler[1][0]; $i++) {
            $this->client->refreshCrawler();
            $data = $this->getContent();
            $tokensWithoutHolders[] = $this->assignMakerAndTakerFrom($data);
            $nextPage = $this->client
                ->findElement(WebDriverBy::cssSelector('#ctl00 > div.d-md-flex.justify-content-between.my-3 > ul > li:nth-child(4) > a'));
            usleep(3000);
            $nextPage->click();
            $this->client->refreshCrawler();
        }
        echo 'Finish getting content ' . date("F j, Y, g:i:s a") . PHP_EOL;;
        echo 'Start assigning holders ' . date("F j, Y, g:i:s a") . PHP_EOL;

        foreach ($tokensWithoutHolders as $packet) {
            $tokensReadyForAlert = $this->proveIfIsWorthToBuyIt($packet);
            if ($tokensReadyForAlert) {
                Factory::createAlert()->sendMessage($packet);
                RedisWriter::writeToRedis($packet);
            }
        }
        echo 'Finish assigning holders ' . date("F j, Y, g:i:s a") . PHP_EOL;

    }

    private function changeOnWebsiteToShowMoreRecords(): void
    {
        try {
            $selectRows = $this->client->findElement(WebDriverBy::id('ContentPlaceHolder1_ddlRecordsPerPage'));
            usleep(30000);
            $webDriverSelect = Factory::createWebDriverSelect($selectRows);
            $webDriverSelect->selectByIndex(self::INDEX_OF_SHOWN_ROWS);
            usleep(30000);
        } catch (NoSuchElementException $exception) {
            echo $exception->getMessage();
        } catch (UnexpectedTagNameException $e) {
            echo $e->getMessage();
        }
    }


    private function getCrawlerForWebsite(
        string $url
    ): void
    {
        $this->client = PantherClient::createChromeClient();
        $this->client->start();
        $this->client->get($url);
        usleep(30000);
        $this->client->refreshCrawler();
        usleep(30000);
    }

    public function getNamesToFindDrop(): array
    {
        return $this->namesToFindDrop;
    }

}