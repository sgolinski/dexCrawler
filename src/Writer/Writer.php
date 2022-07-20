<?php

namespace DexCrawler\Writer;

interface Writer
{
    public static function write(array $makers): void;
}