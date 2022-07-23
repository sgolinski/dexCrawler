<?php

namespace DexCrawler\ValueObjects;

class Holders
{
    public int $holders = 0;

    private function __construct(
        int $holders
    )
    {
        $this->ensureNumberOfHoldersIsBiggerThen($holders);
        $this->holders = $holders;
    }

    public static function fromInt(
        int $numOfHolders
    ): self
    {
        return new self($numOfHolders);
    }

    public function asInt(): int
    {
        return $this->holders;
    }

}