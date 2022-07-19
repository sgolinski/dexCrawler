<?php

namespace DexCrawler\ValueObjects;

class Holders
{
    private float $numOfHolders;

    private const MIN_NUM_OF_HOLDERS = 500;

    private function __construct(
        int $numOfHolders
    )
    {
        $this->ensureNumberOfHoldersIsBiggerThen($numOfHolders);
        $this->numOfHolders = $numOfHolders;
    }

    public static function fromInt(
        int $numOfHolders
    ): self
    {
        return new self($numOfHolders);
    }

    public function asInt(): int
    {
        return $this->numOfHolders;
    }

    private function ensureNumberOfHoldersIsBiggerThen(
        int $numberOfHolders
    ): void
    {
        if ($numberOfHolders < self::MIN_NUM_OF_HOLDERS) {
            throw new \InvalidArgumentException('Expected number of holders it to low');
        }
    }
}