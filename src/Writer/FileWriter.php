<?php

namespace DexCrawler\Writer;

class FileWriter implements Writer
{

    public static function write(array $makers): void
    {
        file_put_contents('/mnt/app/tokens_already_recorded.txt', serialize($makers));
    }

    public static function writeOne(string $alert)
    {
        file_put_contents('tokens_alerts.txt', $alert . PHP_EOL, FILE_APPEND);
    }


}