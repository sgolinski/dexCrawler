<?php
namespace CrawlerCoinGecko;

use Symfony\Component\Panther\Client as PantherClient;

final class PantherClientSingleton
{

    private static ?PantherClient $chromeClient = null;

    private function __construct()
    {
    }

    public static function getChromeClient(): PantherClient
    {
        if (PantherClientSingleton::$chromeClient === null) {
            PantherClientSingleton::$chromeClient = PantherClient::createChromeClient();
        }
        self::$chromeClient->quit();
        PantherClientSingleton::$chromeClient = PantherClient::createChromeClient();
        return PantherClientSingleton::$chromeClient;
    }

}