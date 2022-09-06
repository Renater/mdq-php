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
    if (isSamlEntity($_SERVER['HTTP_USER_AGENT'])) {
        $logger->debug("Unsupported accept value: ".$_SERVER['HTTP_ACCEPT']." but SAML entity => ok");
    } else {
        $logger->error("Unsupported accept value: ".$_SERVER['HTTP_ACCEPT']);
        // http_response_code(406);
        header("Location: /readme");
        exit("Unsupported accept value: ".$_SERVER['HTTP_ACCEPT']);
    }
}

// 2- Decode arguments

if (!isset($_SERVER['PATH_INFO'])) {
    $logger->error("No PATH_INFO provided");
    http_response_code(400);
    exit('Bad request');
}

$logger->debug("Path Info = ".$_SERVER['PATH_INFO']);
list($null, $sources, $type, $entityId) = explode("/", $_SERVER['PATH_INFO'], 4);

if ($type != "entities") {
    $logger->error("Invalid request type: " . $type);
    http_response_code(400);
    exit('Bad request');
}

if (isset($entityId)) {
    // foo+bar/entities/http://my.entity
    $entityId = urldecode($entityId);
    $file  = sha1($entityId) . '.xml';

    $logger->debug("Requested entity ID: ". $entityId . " / file: " . $file);

    $md_found = false;

    // 3.1- First look in $config[federations]
    foreach (explode("+", $sources) as $source) {
        if (!isset($config["federations"][$source])) {
            $logger->error("Unknown source: $source");
            continue;
        }
        $mdFile = $config["federations"][$source]["localPath"] ."/". $file;
        if (file_exists($mdFile)) {
            $federationConfig = $config["federations"][$source];
            $md_found = true;
            break;
        }
    }

    // 3.2- If not found, look in single fede folder
    if (!$md_found && isset($config["federation"])) {
        $mdFile = $config["federation"]["localPath"] ."/". $file;
        if (file_exists($mdFile)) {
            $federationConfig = $config["federation"];
            $md_found = true;
        }
    }

    // 4- Check if file exists
    if (!$md_found) {
        http_response_code(404);
        exit("Unknown entityID ".$entityId);
    }
} else {
    // foo+bar/entities

    if (isset($config["federations"])) {
        // Full entities list is not supported in composed endpoints
        http_response_code(501);
        exit('Not supported');
    } else {
        $mdFile = $config["federation"]["localPath"] ."/". $config["federation"]["metadataFile"];
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
