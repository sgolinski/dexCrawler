<?php

namespace DexCrawler\Reader;

class FileReader implements Reader
{


    public static function read(): array
    {
       return file_get_contents('tokens_already_recorded.txt');
    }
}