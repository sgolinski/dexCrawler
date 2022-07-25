<?php

namespace DexCrawler\Service;


use DexCrawler\Factory;

class Alert
{
    public function invoke(array $tokens): void
    {
        $this->sendMessage($tokens);
    }

    public function sendMessage(array $makers)
    {
        $url = 'http://192.168.178.39/index.php/data';
        $ch = curl_init($url);
        $payload = json_encode($makers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        usleep(10000);
        $result = curl_exec($ch);
        usleep(10000);
        curl_close($ch);

        if ($result) {
            echo 'Sent alert about ' . count($makers) . ' tokens ' . date("F j, Y, g:i:s a") . PHP_EOL;
        } else {
            echo 'No response about ' . count($makers) . ' tokens ' . date("F j, Y, g:i:s a") . PHP_EOL;
        }
    }


    public function sendSlackMessage($slack, array $potentialDrops): void
    {
        $message = Factory::createSlackMessage()->setText($potentialDrops);
        $slack->sendMessage($message);

    }

}