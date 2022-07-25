<?php

namespace DexCrawler\Reader;

use DexCrawler\Datastore\Redis;
use DexCrawler\Entity\Maker;


class RedisReader implements Reader
{
    public static function findKey(string $name): bool
    {
        return Redis::get_redis()->exists($name);
    }

    public static function readTokenByName(string $name): ?Maker
    {
        $token = Redis::get_redis()->get($name);
        if ($token) {
            return unserialize($token);
        }
        return null;
    }
}