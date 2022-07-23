<?php

namespace DexCrawler;

use DexCrawler\Entity\Maker;
use DexCrawler\Entity\Taker;
use DexCrawler\Service\Alert;
use DexCrawler\Service\Crawler;
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
        Taker   $taker,
        int     $created
    ): Maker
    {
        return new Maker($name, $address, $taker, $created);
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

    public static function createCrawlerService(): Crawler
    {
        return new Crawler();
    }

    public static function createAlert(): Alert
    {
        return new Alert();
    }

}