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

    public static function updateMaker(Maker $maker)
    {
        try {
            Redis::get_redis()->del($maker->getName()->asString());
            usleep(20000);
            Redis::get_redis()->set($maker->getName()->asString(), serialize($maker));
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }
}