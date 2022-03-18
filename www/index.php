<?php

include(__DIR__ . '/../lib/init.php');
#require_once "phar://mdq-php.phar/init.php";

global $logger;

// 1- check received request
//    * Must contain entityID attribute (URL encoded)
//    * If not provided, must must provide all the entities (ie MD file)
//    * Check accept is "application/samlmetadata+xml"
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    $logger->error("Non GET method");

    http_response_code(405);
    exit("Non supported method");
}
if ($_SERVER['HTTP_ACCEPT'] != "application/samlmetadata+xml") {
    $logger->error("Unsupported accept value: ".$_SERVER['HTTP_ACCEPT']);
    http_response_code(406);
    exit("Unsupported accept value: ".$_SERVER['HTTP_ACCEPT']);
}

// 2- Decode entityID

$logger->debug("Path Info = ".$_SERVER['PATH_INFO']);
if (!isset($_SERVER['PATH_INFO']) || !startsWith($_SERVER['PATH_INFO'], "/entities")) {
    http_response_code(400);
    exit('Bad request');
}
if (endsWith($_SERVER['PATH_INFO'], "/entities")) {
    if (isset($config["federations"])) {
        // Full entities list is not supported in composed endpoints
        http_response_code(501);
        exit('Not supported');
    } else {
        $mdFile = $config["federation"]["localPath"] ."/". $config["federation"]["metadataFile"];
    }
} else {
    $entityId = extractEntityID($_SERVER['PATH_INFO']);

    $logger->debug("Requested entity ID: ".$entityId." / file: ".sha1($entityId));

    $md_found = false;
    // 3.1- First look in $config[federations]
    foreach ($config["federations"] as $fedeName => $fede) {
        $mdFile = $fede["localPath"] ."/". sha1($entityId) . ".xml";
        if (file_exists($mdFile)) {
            $md_found = true;
            $federationConfig = $fede;
            break;
        }
    }
    // 3.2- If not found, look in single fede folder
    if (!$md_found && isset($config["federation"])) {
        $federationConfig = $config["federation"];
        $mdFile = $config["federation"]["localPath"] ."/". sha1($entityId) . ".xml";
        if (file_exists($mdFile)) {
            $md_found = true;
        }
    }

    // 4- Check if file exists
    if (!$md_found) {
        http_response_code(404);
        exit("Unknown entityID ".$entityId);
    }
}
// 5- Return the file

header('Content-Type: application/samlmetadata+xml');
http_response_code(200);

$xml = simplexml_load_file($mdFile);

if (isset($federationConfig["cacheDuration"])) {
    $xml->addAttribute("cacheDuration", $federationConfig["cacheDuration"]);
}

echo $xml->asXML();
