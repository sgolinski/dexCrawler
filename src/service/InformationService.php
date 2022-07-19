<?php

namespace DexCrawler\service;

use InvalidArgumentException;

class InformationService
{
    public array $information;
    public string $token;
    public float $price;

    private function __construct(
        string $information
    )
    {
        $this->ensureInformationIsNotNull($information);
        $this->information = explode(" ", $information);
        $this->price = $this->extractPriceFrom($this->information[0]);
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

    public function getTokenStringFromInformation(): string
    {
        return $this->token;
    }

    public function getPriceAsFloatFromInformation(): float
    {
        return $this->price;
    }

    private function extractPriceFrom(
        string $float
    ): float
    {
        $strPrice = str_replace([','], [''], $float);

        return round((float)$strPrice, 3);
    }

    private function extractTokenFrom(
        string $data
    ): string
    {
        return strtolower($data);
    }

}