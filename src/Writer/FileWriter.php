<?php

namespace DexCrawler\Writer;

class FileWriter implements Writer
{

    public static function write(array $makers): void
    {
        file_put_contents('tokens_already_recorded.txt', serialize($makers));
    }
}