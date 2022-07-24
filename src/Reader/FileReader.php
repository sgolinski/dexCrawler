<?php

namespace DexCrawler\Reader;

class FileReader
{
    public static function read(): array
    {
        return unserialize(file_get_contents('/mnt/app/tokens_already_recorded.txt'));
    }
}