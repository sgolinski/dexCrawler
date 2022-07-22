<?php

namespace DexCrawler\ValueObjects;

class Name
{
    public string $name;

    public const BLACKLISTED_NAMES = [
        'bnb', 'wbnb', 'eth', 'cake', 'btcb', 'ddao', 'tbac', 'swace', 'sw', 'fgd', 'rld', 'vnt', 'cpad', 'naka',
        'kishurai', 'spacexfalcon', 'sin', 'tube', 'blue', 'vinu', 'codi', 'birdman', 'citi', 'xmx', 'ameta', 'tm',
        'ape', 'hbx', 'dlsc', 'elon', 'klv', 'eshare', 'air', 'fi', 's2k', 'fast', 'pp', 'gvr', 'dexshare', 'chx',
        'mobox', 'lgbt', 'plf', 'google', 'web4', 'iot', 'rpt', 'uki', 'ada', 'spacepi', 'grush', 'mbox', 'pear',
        'time', 'bsw', 'xrp', 'ceek', 'spacepi', 'lego', 'dot', 'metis', 'alt', 'rfx', 'cstc', 'cob', 'xr', 'milo',
        'rfox', 'irt', 'beta', 'safuu', 'usagi', 'sps', 'squidgrow', 'twt', 'sgl', 'tinc', 'st', 'bitsu', 'xvs',
        'supertiger', 'era', 'xiyong', 'elephant', 'thg', 'shino', 'babydoge', 'posi', 'link', 'cats', 'kluv', 'bcake',
        'bsr', '10set', 'spd', 'abnbc', 'seon', 'sfund', 'enft', '$grush', 'debt', 'megaland', 'c98', 'wwy', 'fit',
        'squa', 'raca', 'metis2.0', 'aoco', 'kyoto', 'avax', 'dpet', 'near', 'alu', 'fps', 'godz', 'embr', 'ntrc',
        'ltc', 'copi', 'mtigert', '10set', 'ldoge', 'quick', 'cwt', 'uni', 'hudi', 'brewlabs', 'graviaura', 'tdx', 'move',
        'milk2', 'axs', 'tarp', 'urus', 'gymnet', 'inj', 'zuck', 'bhf', 'doge', 'jpp', 'adog', 'arv', 'linux', 'kos', 'twep',
        'pols', 'mct', 'earn', 'fiu', 'opt', 'dx', 'fxdgp', 'web3', 'spin', 'kitty', 'bshare', 'dogedigger', 'pen', 'bit360',
        'lb', '11up', 'monstr', 'apedao', 'paint', 'amazingteam', 'mbd', 'cum', 'vgxt', 'dma', 'guard', 'rif', 'lblock',
        'brise', 'rodeo', 'myra', 'prcy', 'dut', 'cls', 'yetic', 'usdc', 'busd', 'usdt', 'fusdt', 'usdp', 'bsc-usd'
    ];

    public const ALLOWED_TAKER_TOKENS_NAMES = [
        'wbnb', 'cake', 'bnb', 'usdc', 'busd', 'usdt', 'fusdt', 'usdp'
    ];

    private function __construct(
        string $name
    )
    {
        $name = $this->ensureIsLowerLetter($name);
        $this->name = $name;
    }

    public static function fromString(
        string $name
    ): self
    {
        return new self($name);
    }

    public function asString(): string
    {
        return $this->name;
    }

    public function ensureIsLowerLetter(
        string $str
    ): string
    {
        return strtolower($str);
    }
}