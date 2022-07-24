<?php

use DexCrawler\Factory;
use DexCrawler\Writer\RedisWriter;

require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$crawler = Factory::createCrawlerService();
$alertService = Factory::createAlert();

$crawler->invoke();

$makers = $crawler->getReturnCoins();

if (!empty($markers)) {
    $alertService->invoke($markers);
} else {
    unset($crawler);
    die('Nothing to show ' . date("F j, Y, g:i:s a"));
}
echo 'Downloading information about large movers from last hour ' . date('H:i:s') . PHP_EOL;
echo 'Start saving to Redis ' . date('H:i:s') . PHP_EOL;
RedisWriter::writeToRedis($makers);
echo 'Finish saving to Redis ' . date('H:i:s') . PHP_EOL;