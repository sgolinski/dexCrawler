<?php

namespace DexCrawler\ValueObjects;

class Holders
{
    public int $holders = 0;

    private const MIN_NUM_OF_HOLDERS = 500;

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

    private function ensureNumberOfHoldersIsBiggerThen(
        int $holders
    ): void
    {
        if ($holders < self::MIN_NUM_OF_HOLDERS) {
            throw new \InvalidArgumentException('Expected number of holders it to low');
        }
    }
}