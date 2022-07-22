<?php

namespace DexCrawler\ValueObjects;

class Price
{
    public float $price;

    private function __construct(
        float $price
    )
    {
        $this->price = $price;
    }

    public static function fromFloat(
        float $price
    ): self
    {
        return new self($price);
    }

    public function asFloat(): float
    {
        return $this->price;
    }
}