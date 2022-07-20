<?php

namespace DexCrawler\service;

use DexCrawler\Maker;
use DexCrawler\Writer\FileWriter;
use Maknz\Slack\Client as SlackClient;
use Maknz\Slack\Message;

class AlertService
{
    private SlackClient $slack;

    private const HOOK = 'https://hooks.slack.com/services/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';

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