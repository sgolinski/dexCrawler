<?php

namespace DexCrawler\Reader;

use DexCrawler\Datastore\Redis;

class RedisReader implements Reader
{
    public static function findKey($name): bool
    {
        return Redis::get_redis()->exists($name->asString());
    }
}