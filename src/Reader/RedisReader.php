<?php

namespace DexCrawler\Reader;

use DexCrawler\Datastore\Redis;
use DexCrawler\ValueObjects\Name;

class RedisReader implements Reader
{
    public static function findKey(Name $name): bool
    {
        return Redis::get_redis()->exists($name->asString());
    }
}