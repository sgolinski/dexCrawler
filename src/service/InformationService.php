<?php

namespace DexCrawler\service;

use DexCrawler\ValueObjects\Price;
use DexCrawler\ValueObjects\Token;
use InvalidArgumentException;

class InformationService
{
    public array $information;
    public Token $token;
    public Price $price;

    private function __construct(
        string $information
    )
    {
        $this->ensureInformationIsNotNull($information);
        $this->ensureInformationAfterExplodeHasTwoEntry($information);
        $this->information = explode(" ", $information);
        $this->ensureInformationAboutPriceIsNotNull($this->information[0]);
        $this->price = $this->extractPriceFrom($this->information[0]);
        $this->ensureInformationAboutTokenIsNotNull($this->information[1]);
        $this->token = $this->extractTokenFrom($this->information[1]);
    }

    public static function fromString(
        string $information
    ): self
    {
        return new self($information);
    }

    private function ensureInformationIsNotNull(
        string $information
    ): void
    {
        if ($information === null) {
            throw new InvalidArgumentException('Information is empty!');
        }
    }

    private function ensureInformationAfterExplodeHasTwoEntry(string $information): void
    {
        if (count(explode(" ", $information)) < 2) {
            throw new InvalidArgumentException('Information data has not allowed format');
        }
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    private function extractPriceFrom(
        string $float
    ): Price
    {
        $strPrice = str_replace([','], [''], $float);

        return Price::fromFloat(round((float)$strPrice, 3));
    }

    private function extractTokenFrom(
        string $data
    ): Token
    {
        return Token::fromString(strtolower($data));
    }

    private function ensureInformationAboutPriceIsNotNull(mixed $int)
    {
        if ($int === null) {
            throw new InvalidArgumentException('Information about price is missing');
        }
    }

    private function ensureInformationAboutTokenIsNotNull(mixed $int)
    {
        if ($int === null) {
            throw new InvalidArgumentException('Information about token is missing');
        }
    }

}