<?php

namespace DexCrawler\service;

use ArrayIterator;
use DexCrawler\Factory;
use DexCrawler\Maker;
use DexCrawler\ValueObjects\Address;
use DexCrawler\ValueObjects\Holders;
use DexCrawler\ValueObjects\Name;
use DexCrawler\ValueObjects\Price;
use DexCrawler\ValueObjects\Token;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use InvalidArgumentException;
use Symfony\Component\Panther\Client as PantherClient;

class CrawlerService
{
    private PantherClient $client;
    public static $counter = 0;
    private array $returnCoins = [];
    private const URL = 'https://bscscan.com/dextracker?filter=1';
    private const URL_TOKEN = 'https://bscscan.com/token/';
    private const INDEX_OF_SHOWN_ROWS = 3;
    private const NUMBER_OF_SITES_TO_DOWNLOAD = 50;


    private const SCRIPT = <<<EOF
var selectedA = document.querySelector('#selectDex');
var divWithDexList = document.querySelector('#selectDexButton');
var select = document.getElementById('ContentPlaceHolder1_ddlRecordsPerPage');

selectedA.click();
divWithDexList.querySelector('#selectDexButton > a:nth-child(5) > img').click();
EOF;

    public function invoke()
    {
        try {
            echo "Start crawling " . date("F j, Y, g:i:s a") . PHP_EOL;
            $this->getCrawlerForWebsite(self::URL);
            $this->client->executeScript(self::SCRIPT);
            $this->changeOnWebsiteToShowMoreRecords(self::INDEX_OF_SHOWN_ROWS);
            sleep(1);
            $this->scrappingData(self::NUMBER_OF_SITES_TO_DOWNLOAD);
            $this->logTimeIfEmptyCloseClient();
            $this->returnCoins = $this->proveIfIsWorthToBuyIt($this->returnCoins);
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

                $price = Price::fromFloat($service->getPriceAsFloatFromInformation());
                $tokenNameOfTaker = Token::fromString($service->getTokenStringFromInformation());
                $taker = Factory::createTaker($tokenNameOfTaker, $price,);

                $name = $webElement
                    ->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))
                    ->getText();
                $nameOfMaker = Name::fromString($name);

                $address = $webElement
                    ->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))
                    ->getAttribute('href');
                $makerAddress = Address::fromString($address);

                $maker = Factory::createMaker($nameOfMaker, $makerAddress, $taker);
                $this->returnCoins[] = $maker;

            } catch (InvalidArgumentException) {
                continue;
            }
        }
    }

    public function getReturnCoins(): ?array
    {
        return $this->returnCoins;
    }

    public function __destruct()
    {
    }

    public function proveIfIsWorthToBuyIt(
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
        echo "Validation Finished  " . count($uniqueMakers) . " coins are unique or not scam " . date("F j, Y, g:i:s a") . PHP_EOL;
        return $uniqueMakers;
    }

    public function scrappingData(
        int $numberOfSitesToCollect
    ): void
    {
        for ($i = 0; $i < $numberOfSitesToCollect; $i++) {
           //$this->client->takeScreenshot('page' . $i . '.jpg');
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
    public function changeOnWebsiteToShowMoreRecords(
        int $index
    ): void
    {
        $selectRows = $this->client->findElement(WebDriverBy::id('ContentPlaceHolder1_ddlRecordsPerPage'));
        $webDriverSelect = Factory::createWebDriverSelect($selectRows);
        $webDriverSelect->selectByIndex($index);
    }

    /**
     * @return void
     */
    public function logTimeIfEmptyCloseClient(): void
    {
        echo count($this->returnCoins) . " coins ready for Validation " . date("F j, Y, g:i:s a") . PHP_EOL;
        if (empty($this->returnCoins)) {
            $this->client->close();
            $this->client->quit();
            die();
        }
    }

    /**
     * @return void
     */
    public function getCrawlerForWebsite(
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
    public function returnUniqueArrayFrom(
        array $uniqueMakers,
        Maker $maker,
        bool  $existed
    ): bool
    {
        if (!empty($uniqueMakers)) {
            foreach ($uniqueMakers as $uniqueMaker) {
                assert($uniqueMaker instanceof Maker);
                if ($maker->getName()->asString() === $uniqueMaker->getName()->asString()) {
                    $existed = true;
                }
            }
        }
        return $existed;
    }


}