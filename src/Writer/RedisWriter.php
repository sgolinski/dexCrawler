<?php

namespace DexCrawler\Writer;

use DexCrawler\Datastore\Redis;
use DexCrawler\Entity\Maker;
use Exception;

class RedisWriter
{
    public static function writeToRedis(array $tokens): void
    {
        foreach ($tokens as $token) {
            try {
                assert($token instanceof Maker);
                Redis::get_redis()->set($token->getName()->asString(), serialize($token));
            } catch (Exception $exception) {
                echo $exception->getMessage();
            }
        }
        Redis::get_redis()->save();
    }

}