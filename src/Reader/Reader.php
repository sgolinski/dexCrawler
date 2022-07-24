<?php

namespace DexCrawler\Reader;

interface Reader
{
    public static function findKey($name): bool;
}