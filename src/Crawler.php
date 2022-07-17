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
    private $client;
    private array $returnCoins = [];
    private const SKIPPED_COINS = [
        'bnb', 'wbnb', 'eth', 'cake', 'btcb', 'ddao', 'tbac', 'swace', 'sw', 'fgd', 'rld', 'vnt', 'cpad', 'naka', 'kishurai'
        , 'spacexfalcon', 'sin', 'tube', 'blue', 'vinu', '$codi', 'birdman', 'citi', 'xmx', 'ameta', 'tm', 'ape', 'hbx', 'dlsc', 'elon', 'klv', 'eshare', 'air', 'fi',
        's2k', 'fast', 'pp', 'gvr', 'dexshare', 'chx', 'mobox', 'lgbt', 'plf', 'google', 'web4', 'iot', 'rpt', 'uki'
    ];

    private const SCRIPT = <<<EOF
var selectedA = document.querySelector('#selectDex');
var divWithDexList = document.querySelector('#selectDexButton');
var select = document.getElementById('ContentPlaceHolder1_ddlRecordsPerPage');

selectedA.click();
divWithDexList.querySelector('#selectDexButton > a:nth-child(5) > img').click();
EOF;

    public function __construct()
    {
        $this->client = null;
    }

    public function invoke()
    {
        try {
            $this->client = PantherClient::createChromeClient();
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
            echo 'Downloading information about gainers and losers ' . date("F j, Y, g:i:s a") . PHP_EOL;
        } catch (Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
        } finally {
            $this->client->close();
            $this->client->quit();
        }
    }

    private function getContent(): ?ArrayIterator
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
            $information = $webElement->findElement(WebDriverBy::cssSelector('tr > td:nth-child(5)'))->getText();
            if ($information === null) {
                continue;
            }
            $data = explode(" ", $information);
            $coin = strtolower($data[1]);
            $price = (float)$data[0];

            if ($coin !== 'bnb' && $coin !== 'wbnb' && $coin !== 'cake' && !str_contains($coin, 'usd')) {
                continue;
            }

            if ($coin === 'bnb' || $coin === 'wbnb') {
                if ($price <= 10) {
                    continue;
                }
            } elseif (str_contains($coin, 'usd')) {
                if ($price <= 2475.00) {
                    continue;
                }
            } elseif ($coin === 'cake') {
                if ($price <= 760.00) {
                    continue;
                }
            }

            $name = strtolower($webElement->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))->getText());

            if (in_array($name, self::SKIPPED_COINS)) {
                continue;
            }
            if (str_contains($name, 'usd')) {
                continue;
            }
            $address = $webElement->findElement(WebDriverBy::cssSelector('tr > td:nth-child(3) > a'))->getAttribute('href');

            $holders = (int)$this->proveIfIsWorthToBuyIt($address, $this->client);

            if ($holders < 100) {
                continue;
            }
            $this->returnCoins[] = new Token($name, $price, $address);
        }
        $this->client->close();
        $this->client->quit();
    }

    public function getReturnCoins(): ?array
    {
        return $this->returnCoins;
    }

    public function __destruct()
    {
    }

    private function proveIfIsWorthToBuyIt(?string $address, PantherClient $client): ?string
    {
        $client->refreshCrawler();
        $client->get('https://bscscan.com/' . trim(str_replace('/address/', '/token/', $address)));
        $holders = $client->getCrawler()
            ->filter('#ContentPlaceHolder1_tr_tokenHolders > div > div.col-md-8 > div > div')
            ->getText();

        return $holders;
    }
}