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
use Facebook\WebDriver\Exception\WebDriverCurlException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use InvalidArgumentException;
use Symfony\Component\Panther\Client as PantherClient;

class Crawler
{
    private PantherClient $client;

    private const URL = 'https://bscscan.com/dextracker?filter=1';

    private const URL_TOKEN = 'https://bscscan.com/token/';

    private const INDEX_OF_SHOWN_ROWS = 3;

    private const NUMBER_OF_SITES_TO_DOWNLOAD = 10;

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
            $this->logTimeIfEmptyCloseClient();

        } catch (Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
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
        ArrayIterator $content
    ): array
    {
        $tokensWithoutHolder = [];
        foreach ($content as $webElement) {
            try {
                assert($webElement instanceof RemoteWebElement);
                $information = $webElement
                    ->findElement(WebDriverBy::cssSelector('tr > td:nth-child(5)'))
                    ->getText();

                $service = Information::fromString($information);
                $price = $service->getPrice();
                $tokenNameOfTaker = $service->getToken();
                $taker = Factory::createTaker($tokenNameOfTaker, $price,);
                $name = $webElement
                    ->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))
                    ->getText();

                $nameOfMaker = Name::fromString($name);
                $currentTimestamp = time();

                $maker = RedisReader::findKey($nameOfMaker);
                if ($maker) {
                    continue;
                }
                $address = $webElement
                    ->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))
                    ->getAttribute('href');

                $makerAddress = Address::fromString($address);
                $tokensWithoutHolder[] = Factory::createMaker(
                    $nameOfMaker,
                    $makerAddress,
                    $taker,
                    $currentTimestamp
                );

            } catch (InvalidArgumentException $exception) {
                continue;
            }
        }
        return $tokensWithoutHolder;
    }

    public function proveIfIsWorthToBuyIt($makersWithoutHolders): array
    {
        $tokensForAlert = [];

        foreach ($makersWithoutHolders as $maker) {

            try {
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
                } catch (InvalidArgumentException $exception) {
                    continue;
                }

            } catch (WebDriverCurlException $e) {
                $this->client->close();
                continue;
            }
        }
        echo "Validation Finished  for " . count($tokensForAlert) . " coins are unique or not scam " . date("F j, Y, g:i:s a") . PHP_EOL;
        return $tokensForAlert;
    }

    private function scrappingData(): void
    {
        for ($i = 0; $i < self::NUMBER_OF_SITES_TO_DOWNLOAD; $i++) {
            $this->client->refreshCrawler();
            $data = $this->getContent();
            $tokensWithoutHolders = $this->assignMakerAndTakerFrom($data);
            if (empty($tokensWithoutHolders)) {
                continue;
            }
            $tokensReadyForAlert = $this->proveIfIsWorthToBuyIt($tokensWithoutHolders);
            if (empty($tokensReadyForAlert)) {
                continue;
            }
            Factory::createAlert()->sendMessage($tokensReadyForAlert);
            usleep(90000);
            RedisWriter::writeToRedis($tokensReadyForAlert);
            usleep(30000);
            try {
                $nextPage = $this->client
                    ->findElement(WebDriverBy::cssSelector('#content > div.container.space-bottom-2 > div > div.card-body > div.d-md-flex.justify-content-between.mb-4 > nav > ul > li:nth-child(4) > a'));
            } catch (NoSuchElementException $exception) {
                echo 'error';
                continue;
            }
            usleep(30000);
            $nextPage->click();
            $this->client->refreshCrawler();
        }
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

    private function logTimeIfEmptyCloseClient(int $counter): void
    {
        echo $counter . " coins ready for Validation " . date("F j, Y, g:i:s a") . PHP_EOL;
        if (empty($this->returnCoins) && empty($this->newTokens)) {
            $this->client->close();
            $this->client->quit();
            die();
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

}