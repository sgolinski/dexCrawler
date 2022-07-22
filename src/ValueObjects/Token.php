<?php

namespace DexCrawler\ValueObjects;

class Token
{
    public string $token;

    private function __construct(
        string $token
    )
    {
        $this->token = $token;
    }

    public static function fromString(
        string $token
    ): self
    {
        return new self($token);
    }

    public function asString(): string
    {
        return $this->token;
    }

}