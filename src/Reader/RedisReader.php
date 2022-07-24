<?php

namespace DexCrawler\Reader;

use DexCrawler\Datastore\Redis;
use DexCrawler\Entity\Maker;

class RedisReader
{
    public static function readMakerByName(string $name): ?Maker
    {
        $token = Redis::get_redis()->get($name);
        if ($token) {
            return unserialize($token);
        }
        return null;
    }

    public static function findKey($name)
    {
        return Redis::get_redis()->exists($name->asString());
    }
}