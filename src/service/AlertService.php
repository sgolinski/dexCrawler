<?php

namespace DexCrawler\service;


use DexCrawler\Maker;
use Maknz\Slack\Client as SlackClient;
use Maknz\Slack\Message;

class AlertService
{
    private SlackClient $slack;

    private const HOOK = 'https://hooks.slack.com/service/T0315SMCKTK/B03160VKMED/hc0gaX0LIzVDzyJTOQQoEgUE';

    public function __construct()
    {
        $this->slack = new SlackClient(self::HOOK);
    }

    public function invoke(array $tokens): void
    {
        $this->sendMessage($tokens);
    }

    public function sendMessage(array $makers)
    {
        foreach ($makers as $maker) {
            assert($maker instanceof Maker);
            $message = new Message();
            $message->setText($maker->alert());
            $this->slack->sendMessage($message);
        }
        echo 'Sent alert about ' . count($makers) . ' tokens ' . date("F j, Y, g:i:s a") . PHP_EOL;
    }


}