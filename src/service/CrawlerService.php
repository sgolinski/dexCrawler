<?php

namespace DexCrawler\service;

use ArrayIterator;
use DexCrawler\Factory;
use DexCrawler\Maker;
use DexCrawler\Reader\FileReader;
use DexCrawler\ValueObjects\Address;
use DexCrawler\ValueObjects\Holders;
use DexCrawler\ValueObjects\Name;
use DexCrawler\Writer\FileWriter;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use InvalidArgumentException;
use Symfony\Component\Panther\Client as PantherClient;

class CrawlerService
{
    private PantherClient $client;
    private array $returnCoins = [];
    private const URL = 'https://bscscan.com/dextracker?filter=1';
    private const URL_TOKEN = 'https://bscscan.com/token/';
    private const INDEX_OF_SHOWN_ROWS = 3;
    private const NUMBER_OF_SITES_TO_DOWNLOAD = 10;
    public static array $recordedArray;
    public array $newTokens = [];


    private const SCRIPT = <<<EOF
var selectedA = document.querySelector('#selectDex');
var divWithDexList = document.querySelector('#selectDexButton');
var select = document.getElementById('ContentPlaceHolder1_ddlRecordsPerPage');

selectedA.click();
divWithDexList.querySelector('#selectDexButton > a:nth-child(5) > img').click();
EOF;

    public function __construct()
    {
        self::$recordedArray = FileReader::read();
    }

    public function invoke()
    {
        try {
            echo "Start crawling " . date("F j, Y, g:i:s a") . PHP_EOL;
            $this->getCrawlerForWebsite(self::URL);
            $this->client->executeScript(self::SCRIPT);
            $this->changeOnWebsiteToShowMoreRecords();
            sleep(1);
            $this->scrappingData();
            $this->logTimeIfEmptyCloseClient();

            $this->newTokens = $this->proveIfIsWorthToBuyIt($this->newTokens);
            $this->returnCoins = array_merge($this->newTokens, $this->returnCoins);

            FileWriter::write(self::$recordedArray);

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
            assert($webElement instanceof RemoteWebElement);

            try {

                $information = $webElement
                    ->findElement(WebDriverBy::cssSelector('tr > td:nth-child(5)'))
                    ->getText();
                $service = InformationService::fromString($information);

                $price = $service->getPrice();
                $tokenNameOfTaker = $service->getToken();
                $taker = Factory::createTaker($tokenNameOfTaker, $price,);

                $name = $webElement
                    ->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))
                    ->getText();
                $nameOfMaker = Name::fromString($name);

                $currentTimestamp = time();


                if (!empty(self::$recordedArray) && $this->checkIfRecordExistInRecordedArray($nameOfMaker) !== null) {

                    $updatedMaker = $this->checkIfRecordExistInRecordedArray($nameOfMaker);
                    if ($updatedMaker->getHolders()->asInt() > 500) {
                        //                  var_dump($updatedMaker->getHolders()->asInt());
                        $this->checkIfIsNotToNew($updatedMaker, $currentTimestamp);
                        $updatedMaker->getTaker()->updateDropValue($price);

                        if ($this->checkIfIsNotToNew($updatedMaker, $currentTimestamp)) {
                            $this->returnCoins[] = $updatedMaker;
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                } else {
                    $address = $webElement
                        ->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))
                        ->getAttribute('href');
                    $makerAddress = Address::fromString($address);
                    $maker = Factory::createMaker($nameOfMaker, $makerAddress, $taker, $currentTimestamp);
                    self::$recordedArray[] = $maker;
                    $this->newTokens[] = $maker;
                }
            } catch (InvalidArgumentException) {
                continue;
            }
        }
    }

    private function proveIfIsWorthToBuyIt(
        array $makers
    ): array
    {
        $uniqueMakers = [];
        foreach ($makers as $maker) {
            $existed = false;
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
            $existed = $this->returnUniqueArrayFrom($uniqueMakers, $maker, $existed);

            if (!$existed) {
                $uniqueMakers[] = $maker;
            }
        }
        echo "Validation Finished  " . count($uniqueMakers) + count($this->returnCoins) . " coins are unique or not scam " . date("F j, Y, g:i:s a") . PHP_EOL;
        return $uniqueMakers;
    }

    private function scrappingData(): void
    {
        for ($i = 0; $i < self::NUMBER_OF_SITES_TO_DOWNLOAD; $i++) {
            $this->client->refreshCrawler();
            $data = $this->getContent();
            $this->assignMakerAndTakerFrom($data);
            $nextPage = $this->client
                ->findElement(WebDriverBy::cssSelector('#content > div.container.space-bottom-2 > div > div.card-body > div.d-md-flex.justify-content-between.mb-4 > nav > ul > li:nth-child(4) > a'));
            usleep(200);
            $nextPage->click();
            $this->client->refreshCrawler();
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

    /**
     * @return void
     */
    private function logTimeIfEmptyCloseClient(): void
    {
        echo count($this->returnCoins) + count($this->newTokens) . " coins ready for Validation " . date("F j, Y, g:i:s a") . PHP_EOL;
        if (empty($this->returnCoins) && empty($this->newTokens)) {
            $this->client->close();
            $this->client->quit();
            die();
        }
    }

    /**
     * @return void
     */
    private function getCrawlerForWebsite(
        string $url
    ): void
    {
        $this->client = PantherClient::createChromeClient();
        $this->client->start();
        $this->client->get($url);
        $this->client->refreshCrawler();
    }

    /**
     * @param array $uniqueMakers
     * @param Maker $maker
     * @param bool $existed
     * @return bool
     */
    private function returnUniqueArrayFrom(
        array $uniqueMakers,
        Maker $maker,
        bool  $existed
    ): bool
    {
        if (!empty($uniqueMakers)) {
            foreach ($uniqueMakers as $uniqueMaker) {
                assert($uniqueMaker instanceof Maker);
                if ($maker->getName()->asString() === $uniqueMaker->getName()->asString()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getReturnCoins(): ?array
    {
        return $this->returnCoins;
    }

    public function __destruct()
    {
    }

    public function checkIfRecordExistInRecordedArray(Name $name): ?Maker
    {
        foreach (self::$recordedArray as $maker) {
            assert($maker instanceof Maker);
            if ($maker->getName()->asString() === $name->asString()) {
                return $maker;
            }
        }
        return null;
    }

    private function checkIfIsNotToNew(Maker $updatedMaker, int $currentTimestamp): bool
    {
        if ($currentTimestamp - $updatedMaker->getCreated() < 3600) {
            $updatedMaker->updateCreated($currentTimestamp);
            return true;
        }
        $updatedMaker->updateCreated($currentTimestamp);
        return false;
    }

}