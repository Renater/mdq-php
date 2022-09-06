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
$params = explode("/", $_SERVER['PATH_INFO'], 4);

if (!isset($params[1]) || !isset($params[2]) || $params[2] != "entities") {
    $logger->error("Invalid PATH_INFO: " . $_SERVER['PATH_INFO']);
    http_response_code(400);
    exit('Bad request');
}

if (isset($params[3])) {
    // foo+bar/entities/http://my.entity
    $entityId = urldecode($params[3]);
    $file  = sha1($entityId) . '.xml';

    $logger->debug("Requested entity ID: ". $entityId . " / file: " . $file);

    $md_found = false;

    // 3.1- First look in $config[federations]
    foreach (explode("+", $params[1]) as $source) {
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

    $xml = simplexml_load_file($mdFile);

    if (isset($federationConfig["cacheDuration"])) {
        $xml->addAttribute("cacheDuration", $federationConfig["cacheDuration"]);
    }

    header('Content-Type: application/samlmetadata+xml');
    http_response_code(200);
    echo $xml->asXML();
} else {
    // foo+bar/entities

    $doc = new DOMDocument();
    $doc->loadXML(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<md:EntitiesDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata">
</md:EntitiesDescriptor>
XML
    );
    $node = $doc->documentElement;

    foreach (explode("+", $params[1]) as $source) {
        if (!isset($config["federations"][$source])) {
            $logger->error("Unknown source: $source");
            continue;
        }
        foreach(glob($config["federations"][$source]["localPath"] . "/*.xml") as $file) {
            $entity_doc = new DOMDocument();
            $entity_doc->load($file);
            $entity_node = $doc->importNode($entity_doc->documentElement, true);
            $node->appendChild($entity_node);
        }
    }

    header('Content-Type: application/samlmetadata+xml');
    http_response_code(200);
    echo $doc->saveXML();
}
