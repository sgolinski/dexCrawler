<?php

namespace DexCrawler\Writer;

use DexCrawler\Entity\Maker;

interface Writer
{
    public static function write(array $makers): void;

}