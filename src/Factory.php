<?php

namespace DexCrawler;

use DexCrawler\service\AlertService;
use DexCrawler\service\CrawlerService;
use DexCrawler\ValueObjects\Address;
use DexCrawler\ValueObjects\Name;
use DexCrawler\ValueObjects\Price;
use DexCrawler\ValueObjects\Token;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverSelect;

class Factory
{
    public static function createMaker(
        Name    $name,
        Address $address,
        Taker   $taker
    ): Maker
    {
        return new Maker($name, $address, $taker);
    }

    public static function createTaker(
        Token $token,
        Price $dropValue
    ): Taker
    {
        return new Taker($token, $dropValue);
    }

    /**
     * @throws \Facebook\WebDriver\Exception\UnexpectedTagNameException
     */
    public static function createWebDriverSelect(
        WebDriverElement $element
    ): WebDriverSelect
    {
        return new WebDriverSelect($element);
    }

    public static function createCrawlerService(): CrawlerService
    {
        return new CrawlerService();
    }

    public static function createAlert(): Alert
    {
        return new Alert();
    }

}