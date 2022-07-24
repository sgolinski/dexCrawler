<?php

namespace DexCrawler\Service;

use ArrayIterator;
use DexCrawler\Entity\Maker;
use DexCrawler\Factory;
use DexCrawler\Reader\FileReader;
use DexCrawler\Reader\RedisReader;
use DexCrawler\ValueObjects\Address;
use DexCrawler\ValueObjects\Holders;
use DexCrawler\ValueObjects\Name;
use DexCrawler\Writer\FileWriter;
use DexCrawler\Writer\RedisWriter;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Panther\Client as PantherClient;

class Crawler
{
    public static $counter = 0;

    private PantherClient $client;

    private array $returnCoins = [];

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
    ): void
    {

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

                $maker = RedisReader::findKey($nameOfMaker->asString());
                if ($maker) {
                    continue;
                }
                $address = $webElement
                    ->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))
                    ->getAttribute('href');

                $makerAddress = Address::fromString($address);
                $this->returnCoins[] = Factory::createMaker(
                    $nameOfMaker,
                    $makerAddress,
                    $taker,
                    $currentTimestamp
                );

            } catch (InvalidArgumentException $exception) {
                continue;
            }
        }
    }

    public function proveIfIsWorthToBuyIt(): void
    {
        foreach ($this->returnCoins as $maker) {

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
                } catch (InvalidArgumentException $exception) {
                    continue;
                }

            } catch
            (InvalidArgumentException $e) {
                continue;
            }
        }
        Factory::createAlert()->sendMessage($this->returnCoins);
        RedisWriter::writeToRedis($this->returnCoins);

        echo "Validation Finished  for " . count($this->returnCoins) . " coins are unique or not scam " . date("F j, Y, g:i:s a") . PHP_EOL;
    }

    private function scrappingData(): void
    {
        for ($i = 0; $i < self::NUMBER_OF_SITES_TO_DOWNLOAD; $i++) {
            $this->client->refreshCrawler();
            $data = $this->getContent();
            $this->assignMakerAndTakerFrom($data);
            $this->proveIfIsWorthToBuyIt();
            $nextPage = $this->client
                ->findElement(WebDriverBy::cssSelector('#content > div.container.space-bottom-2 > div > div.card-body > div.d-md-flex.justify-content-between.mb-4 > nav > ul > li:nth-child(4) > a'));
            usleep(200);
            $nextPage->click();
            $this->client->refreshCrawler();
            $this->returnCoins = [];
        }
    }

    /**
     * @return void
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\UnexpectedTagNameException
     */
    private function changeOnWebsiteToShowMoreRecords(): void
    {
        $selectRows = $this->client->findElement(WebDriverBy::id('ContentPlaceHolder1_ddlRecordsPerPage'));
        $webDriverSelect = Factory::createWebDriverSelect($selectRows);
        $webDriverSelect->selectByIndex(self::INDEX_OF_SHOWN_ROWS);
    }

    private function logTimeIfEmptyCloseClient(): void
    {
        echo count($this->returnCoins) + count($this->newTokens) . " coins ready for Validation " . date("F j, Y, g:i:s a") . PHP_EOL;
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
        $this->client->refreshCrawler();
    }

    public function getReturnCoins(): ?array
    {
        return $this->returnCoins;
    }

}