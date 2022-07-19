<?php

use DexCrawler\Factory;
use DexCrawler\Alert;
use DexCrawler\service\CrawlerService;

require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$crawler = Factory::createCrawlerService();
$alertService = Factory::createAlert();

$crawler->invoke();

$markers = $crawler->getReturnCoins();

if (!empty($markers)) {
    $alertService->invoke($markers);
} else {
    unset($crawler);
    die('Nothing to show ' . date("F j, Y, g:i:s a"));
}