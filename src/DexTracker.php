<?php

namespace CrawlerCoinGecko;

use Exception;
use Maknz\Slack\Attachment;
use Maknz\Slack\Client as SlackClient;
use Maknz\Slack\Message;

class DexTracker
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

    public function sendMessage(array $tokens)
    {
        foreach ($tokens as $coin) {
            assert($coin instanceof Token);
            $message = new Message();
            $message->setText($coin->getDescription());
            $this->slack->sendMessage($message);
        }
    }


}