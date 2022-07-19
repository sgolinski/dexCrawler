<?php

namespace DexCrawler\ValueObjects;

class Address
{
    private string $address;

    private function __construct(
        string $address
    )
    {
        $this->address = trim(str_replace('/address/', '', $address));
    }

    public static function fromString(
        string $address
    ): self
    {
        return new self($address);
    }

    public function asString(): string
    {
        return $this->address;
    }

}