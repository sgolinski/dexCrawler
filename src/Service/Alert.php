<?php

namespace DexCrawler\Service;


class Alert
{
    public function invoke(array $tokens): void
    {
        $this->sendMessage($tokens);
    }

    public function sendMessage(array $makers)
    {
        usleep(10000);
        $url = 'http://192.168.178.39/index.php/data';
        usleep(10000);
        $ch = curl_init($url);
        usleep(10000);
        $payload = json_encode($makers);
        usleep(10000);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        usleep(10000);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        usleep(10000);
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


}