<?php

use DexCrawler\Factory;
use DexCrawler\Writer\RedisWriter;

require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$crawler = Factory::createCrawlerService();

$crawler->invoke();

echo 'Cronjob finished ' . date('H:i:s') . PHP_EOL;