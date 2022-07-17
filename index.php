<?php

use CrawlerCoinGecko\DexTracker;
use CrawlerCoinGecko\Crawler;

require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$crawler = new Crawler();
$dex = new DexTracker();

$crawler->invoke();
$array = $crawler->getReturnCoins();
$crawler->__destruct();

if ($array !== null) {
    $dex->invoke($crawler->getReturnCoins());
} else {
    unset($dex);
    die('nothing to show');
}
