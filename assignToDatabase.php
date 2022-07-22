<?php

use DexCrawler\Factory;
use DexCrawler\Maker;
use DexCrawler\Reader\FileReader;
use DexCrawler\service\CrawlerService;
use DexCrawler\Writer\FileWriter;

require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$host = "127.0.0.1:3306";
$user = "root";
$pass = "alerts";
$db = "alerts";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $mysqli = new mysqli("127.0.0.1:3306", "root", "alerts", "alerts");
    $mysqli->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log($e->getMessage());
    exit('Error connecting to database'); //Should be a message a typical user could understand
}


$array = FileReader::read();


//$crawler = new CrawlerService();
//
//$arrayWithHolders = $crawler->proveIfIsWorthToBuyIt($array);
//
//FileWriter::write($arrayWithHolders);

foreach ($array as $maker) {

    assert($maker instanceof Maker);
//    var_dump($maker->getHolders()->numOfHolders); die;
    $sql = 'INSERT INTO makers(name,address, holders,token, dropValue, externalLinks, created) VALUES (?,?,?,?,?,?,?)';


    $stmt = $mysqli->prepare($sql);

    $name = $maker->getName()->asString();
    $address = $maker->getAddress()->asString();
    $holders = $maker->getHolders()->numOfHolders;
    $token = $maker->getTaker()->getToken()->asString();
    $dropValue = $maker->getTaker()->getDropValue()->asFloat();
    $serializeExternalLinks = $maker->alert();
    $date = date("Y-m-d H:i:s", $maker->getCreated());
    $holders = 0;

    $stmt->bind_param(
        "ssdsdss",
        $name,
        $address,
        $holders,
        $token,
        $dropValue,
        $serializeExternalLinks,
        $date
    );
    $stmt->execute();
    $stmt->close();
}