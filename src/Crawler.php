<?php

namespace CrawlerCoinGecko;

use ArrayIterator;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Symfony\Component\Panther\Client as PantherClient;

class Crawler
{
    private $client;
    public static $counter = 0;
    private array $returnCoins = [];
    private const SKIPPED_COINS = [
        'bnb', 'wbnb', 'eth', 'cake', 'btcb', 'ddao', 'tbac', 'swace', 'sw', 'fgd', 'rld', 'vnt', 'cpad', 'naka', 'kishurai'
        , 'spacexfalcon', 'sin', 'tube', 'blue', 'vinu', '$codi', 'birdman', 'citi', 'xmx', 'ameta', 'tm', 'ape', 'hbx', 'dlsc', 'elon', 'klv', 'eshare', 'air', 'fi',
        's2k', 'fast', 'pp', 'gvr', 'dexshare', 'chx', 'mobox', 'lgbt', 'plf', 'google', 'web4', 'iot', 'rpt', 'uki', 'ada', 'spacepi', '$grush', 'mbox', 'pear', 'time', 'bsw', 'xrp',
        'ceek', 'spacepi'
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

            echo "Start crawling " . date("F j, Y, g:i:s a") . PHP_EOL;
            $this->client = PantherClient::createChromeClient();
            $this->client->start();
            $this->client->get('https://bscscan.com/dextracker?filter=1');
            $this->client->executeScript(self::SCRIPT);
            $selectRows = $this->client->findElement(WebDriverBy::id('ContentPlaceHolder1_ddlRecordsPerPage'));
            $webDriverSelect = new WebDriverSelect($selectRows);
            $webDriverSelect->selectByIndex(3);
            sleep(1);

            for ($i = 0; $i < 50; $i++) {
                //             $this->client->takeScreenshot('page' . $i . '.jpg');
                $this->client->refreshCrawler();
                $data = $this->getContent();
                $this->getBnbOrUsd($data);
                $nextPage = $this->client->findElement(WebDriverBy::cssSelector('#content > div.container.space-bottom-2 > div > div.card-body > div.d-md-flex.justify-content-between.mb-4 > nav > ul > li:nth-child(4) > a'));
                usleep(200);
                $nextPage->click();
                $this->client->refreshCrawler();
            }
            echo "Validation " . date("F j, Y, g:i:s a") . PHP_EOL;
            if (!empty($this->returnCoins)) {
                $this->returnCoins = $this->proveIfIsWorthToBuyIt($this->client, $this->returnCoins);
            }
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
            //echo self::$counter++ . PHP_EOL;
            $information = $webElement->findElement(WebDriverBy::cssSelector('tr > td:nth-child(5)'))->getText();
            if ($information === null) {
                continue;
            }
            $data = explode(" ", $information);
            $coin = strtolower($data[1]);
            $price = round((float)$data[0], 1);

            if ($coin !== 'bnb' && $coin !== 'wbnb' && $coin !== 'cake' && !str_contains($coin, 'usd')) {
                continue;
            }

            if ($coin === 'bnb' || $coin === 'wbnb') {
                if ($price <= 10) {
                    continue;
                }
            } elseif (str_contains($coin, 'usd')) {
                if ($price <= 2470.00) {
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

            $this->returnCoins[] = new Token($name, $price . ' ' . $coin, $address);
        }

    }

    public function getReturnCoins(): ?array
    {
        return $this->returnCoins;
    }

    public function __destruct()
    {
    }

    public function proveIfIsWorthToBuyIt(PantherClient $client, $coinsToCheck): array
    {
        $coins = [];
        foreach ($coinsToCheck as $coin) {
            $existed = false;
            assert($coin instanceof Token);
            $client->refreshCrawler();
            $client->get('https://bscscan.com/token/' . $coin->getAddress());
            $holdersString = $client->getCrawler()
                ->filter('#ContentPlaceHolder1_tr_tokenHolders > div > div.col-md-8 > div > div')
                ->getText();
            $holders = (int)str_replace(',', "", explode(' ', $holdersString)[0]);

            if (!empty($coins)) {
                foreach ($coins as $checkUnique) {
                    if ($checkUnique->getName() === $coin->getName()) {
                        $existed = true;
                    }
                }
            }

            if ($holders > 500 && !$existed) {
                $coins[] = $coin;
            }
        }
        return $coins;
    }


}