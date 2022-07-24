<?php

namespace DexCrawler\Reader;

use DexCrawler\ValueObjects\Name;

interface Reader
{
    public static function findKey(Name $name): bool;
}