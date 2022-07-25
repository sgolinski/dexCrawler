<?php

use DexCrawler\Entity\Maker;
use DexCrawler\Factory;
use DexCrawler\Reader\RedisReader;
use DexCrawler\Service\Crawler;
use DexCrawler\ValueObjects\Name;
use DexCrawler\Datastore\Redis;

require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$crawler = Factory::createCrawlerService();

$crawler->invoke();

echo 'Cronjob finished ' . date('H:i:s') . PHP_EOL;
if (Redis::get_redis()->dbsize() > 300) {
    Redis::get_redis()->flushall();
}
$potentialDrop = array_count_values($crawler->getNamesToFindDrop());

$returnDrop = [];

foreach ($potentialDrop as $key => $value) {
    $key = strtolower(trim($key));
    if (!in_array($key, Name::$blackListedCoins) && !in_array($key, Name::$allowedTakerNames)) {
        if (Crawler::$valuesForCrawler[1][1] < (int)$value) {
            if (RedisReader::findKey($key)) {
                $maker = RedisReader::readTokenByName($key);
                assert($maker instanceof Maker);
                $returnDrop[] = $maker->alert();
            } else {
                $returnDrop[] = $key . PHP_EOL;
            }
        }
    }
}
if (!empty($returnDrop)) {
    Factory::createAlert()->sendSlackMessage(Factory::createSlackClient(Crawler::$hook), $returnDrop);
}
