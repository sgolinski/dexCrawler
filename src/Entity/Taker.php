<?php

namespace DexCrawler\Entity;

use DexCrawler\ValueObjects\Name;
use DexCrawler\ValueObjects\Price;
use DexCrawler\ValueObjects\Token;
use InvalidArgumentException;

class Taker
{
    public Token $token;

    public Price $dropValue;

    public const ALLOWED_PRICE_PER_TOKEN =
        [
            'wbnb' => 10.00,
            'cake' => 760.00,
            'bnb' => 10.00,
            'usdc' => 2470.00,
            'busd' => 2470.00,
            'usdt' => 2470.00,
            'fusdt' => 2470.00,
            'usdp' => 2470.00
        ];

    public function __construct(
        Token $token,
        Price $dropValue
    )
    {
        $this->ensureIsAllowedTakerToken($token);
        $this->ensureDropPriceIsHighEnough($token, $dropValue);
        $this->token = $token;
        $this->dropValue = $dropValue;
    }

    public function ensureIsAllowedTakerToken(Token $token): void
    {
        if (!in_array($token->asString(), Name::ALLOWED_TAKER_TOKENS_NAMES)) {
            throw new InvalidArgumentException('Token not allowed');
        }
    }

    private function ensureDropPriceIsHighEnough(
        Token $token,
        Price $dropValue
    ): void
    {
        if ($dropValue->asFloat() < self::ALLOWED_PRICE_PER_TOKEN[$token->asString()]) {
            throw new InvalidArgumentException('Price is not high enough');
        }
    }

    /**
     * @return Token
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    /**
     * @return Price
     */
    public function getDropValue(): Price
    {
        return $this->dropValue;
    }

    public function updateDropValue(Price $dropValue): void
    {
        $this->dropValue = $dropValue;
    }


}