<?php

namespace DexCrawler;

class Token
{
    public string $name;
    public string $dropValue;
    public string $address;
    public string $cmcLink;
    public string $coingeckoLink;
    public string $poocoinLink;


    public function __construct($name, $dropValue, $address,)
    {
        $this->name = strtolower($name);
        $this->dropValue = $dropValue;
        $this->address = trim(str_replace('/address/', '', $address));
        $this->cmcLink = 'https://coinmarketcap.com/currencies/' . $this->name;
        $this->coingeckoLink = 'https://www.coingecko.com/en/coins/' . $this->address;
        $this->poocoinLink = 'https://poocoin.app/tokens/' . $this->address;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDropValue(): string
    {
        return $this->dropValue;
    }


    /**
     * @return string
     */
    public function getCmcLink(): string
    {
        return $this->cmcLink;
    }

    /**
     * @return string
     */
    public function getCoingeckoLink(): string
    {
        return $this->coingeckoLink;
    }

    /**
     * @return string
     */
    public function getPoocoinLink(): string
    {
        return $this->poocoinLink;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }



    public function getDescription(): ?string
    {

        return "Name: " . $this->getName() . PHP_EOL .
            "Drop value: -" . $this->getDropValue() . PHP_EOL .
            "Cmc: " . $this->getCmcLink() . PHP_EOL .
            "DexTracker: " . $this->getCoingeckoLink() . PHP_EOL .
            "Poocoin: " . $this->getPoocoinLink() . PHP_EOL;
    }
}