<?php

use DexCrawler\service\AlertService;
use DexCrawler\service\CrawlerService;

require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$crawler = new CrawlerService();
$dex = new AlertService();

$crawler->invoke();
$array = $crawler->getReturnCoins();
$crawler->__destruct();

if ($array !== null) {
    $dex->invoke($crawler->getReturnCoins());
} else {
    unset($dex);
    die('nothing to show');
}
