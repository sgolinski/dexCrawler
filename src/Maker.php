<?php

namespace DexCrawler;

use DateTime;
use DexCrawler\ValueObjects\Address;
use DexCrawler\ValueObjects\Holders;
use DexCrawler\ValueObjects\Name;
use InvalidArgumentException;

class Maker
{
    public Name $name;
    public Address $address;
    public ?Holders $holders;
    public Taker $taker;
    public array $externalListingLinks;
    public int $created;

    public function __construct(
        Name    $name,
        Address $address,
        Taker   $taker,
        int     $created
    )
    {
        $this->ensureTokenNameIsNotBlacklisted($name->asString());
        $this->name = $name;
        $this->address = $address;
        $this->taker = $taker;
        $this->created = $created;
        $this->holders = null;
        $this->setLinkToListings();
    }

    public function ensureTokenNameIsNotBlacklisted(
        string $name
    ): void
    {
        if (in_array($name, NAME::BLACKLISTED_NAMES)) {
            throw new InvalidArgumentException('Token is on the blacklist');
        }
    }

    public function setLinkToListings(): void
    {
        $this->externalListingLinks = [
            'cmc' => 'https://coinmarketcap.com/currencies/' . $this->name->asString(),
            'coingecko' => 'https://www.coingecko.com/en/coins/' . $this->address->asString(),
            'poocoin' => 'https://poocoin.app/tokens/' . $this->address->asString(),
        ];
    }

    public function alert(): string
    {
        return "Name: " . $this->name->asString() . PHP_EOL .
            "Drop value: -" . $this->getTaker()->getDropValue()->asFloat() . ' ' . $this->getTaker()->getToken()->asString() . PHP_EOL .
            "Cmc: " . $this->getExternalListingByIndex('cmc') . PHP_EOL .
            "Coingecko: " . $this->getExternalListingByIndex('coingecko') . PHP_EOL .
            "Poocoin: " . $this->getExternalListingByIndex('poocoin') . PHP_EOL;
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @return Name
     */
    public function getName(): Name
    {
        return $this->name;
    }

    public function setHolders(Holders $holders)
    {
        $this->holders = $holders;
    }

    /**
     * @return Holders
     */
    public function getHolders(): Holders
    {
        return $this->holders;
    }

    /**
     * @return Taker
     */
    public function getTaker(): Taker
    {
        return $this->taker;
    }

    /**
     * @return string
     */
    public function getExternalListingByIndex(
        string $index
    ): string
    {
        return $this->externalListingLinks[$index];
    }

    /**
     * @return int
     */
    public function getCreated(): int
    {
        return $this->created;
    }

    public function updateCreated(int $time)
    {
        $this->created = $time;
    }

}