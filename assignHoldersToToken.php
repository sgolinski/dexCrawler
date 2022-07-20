<?php


use DexCrawler\Factory;
use DexCrawler\Alert;
use DexCrawler\service\CrawlerService;

require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$crawler = Factory::createCrawlerService();
$alertService = Factory::createAlert();
