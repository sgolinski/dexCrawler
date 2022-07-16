<?php

namespace CrawlerCoinGecko;

use ArrayIterator;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Symfony\Component\Panther\Client as PantherClient;

class Crawler
{
    private PantherClient $client;
    private array $returnCoins = [];
    private const SCRIPT = <<<EOF
var selectedA = document.querySelector('#selectDex');
var divWithDexList = document.querySelector('#selectDexButton');
var select = document.getElementById('ContentPlaceHolder1_ddlRecordsPerPage');

selectedA.click();
divWithDexList.querySelector('#selectDexButton > a:nth-child(5) > img').click();
EOF;

    private const SCRIPT_RELOAD = <<<EOF
location.reload();
EOF;

    public function __construct()
    {
        $this->client = PantherClientSingleton::getChromeClient();

    }

    public function invoke()
    {
        try {
            $this->client->start();
            $this->client->get('https://bscscan.com/dextracker?filter=1');
            $this->client->executeScript(self::SCRIPT);
            $selectRows = $this->client->findElement(WebDriverBy::id('ContentPlaceHolder1_ddlRecordsPerPage'));
            $webDriver = new WebDriverSelect($selectRows);
            $webDriver->selectByIndex(3);
            sleep(1);
            $this->client->refreshCrawler();
            $data = $this->getContent();
            $this->getBNBorUSD($data);
            $this->client->executeScript(self::SCRIPT_RELOAD);
            echo 'Downloading information about gainers and losers ' . date("F j, Y, g:i:s a") . PHP_EOL;

        } catch (Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
        } finally {
            $this->client->quit();
        }
    }

    private function getContent(): ArrayIterator
    {
        $list = null;

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

    private function getBnbOrUsd(ArrayIterator $content)
    {

        foreach ($content as $webElement) {

            assert($webElement instanceof RemoteWebElement);
            $price = null;
            $address = null;
            $name = null;
            $information = $webElement->findElement(WebDriverBy::cssSelector('tr > td:nth-child(5)'))->getText();

            if (str_contains($information, 'BNB')) {
                $priceInBNB = (float)explode(" ", $information)[0];
                if ($priceInBNB >= 10) {
                    $price = $information;
                    $address = $webElement->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))->getAttribute('href');
                    $name = $webElement->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))->getText();
                }
            }
            if (str_contains($information, 'USD')) {
                $priceInUSD = (float)explode(" ", $information)[0];
                if ($priceInUSD >= 2475.00) {
                    $price = $information;
                    $address = $webElement->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))->getAttribute('href');
                    $name = $webElement->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))->getText();
                }
            }

            if (str_contains($information, 'CAKE')) {
                $priceInCAKE = (float)explode(" ", $information)[0];
                if ($priceInCAKE >= 760.00) {
                    $price = $information;
                    $address = $webElement->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))->getAttribute('href');
                    $name = $webElement->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))->getText();
                }
            }
            if ($price !== null && !str_contains($name, 'BNB')
                && !str_contains($name, 'USD')
                && !str_contains($name, 'ETH')
                && !str_contains($name, 'CAKE')
            ) {
                $this->returnCoins[] = new Token($name, $price, $address);
            }
        }
    }


    public function getReturnCoins(): ?array
    {
        return $this->returnCoins;
    }


    public function getClient(): PantherClient
    {
        return $this->client;
    }

}