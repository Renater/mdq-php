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

    // Look in each metadata source
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

    // Check if file exists
    if (!$md_found) {
        http_response_code(404);
        exit("Unknown entityID ".$entityId);
    }

    $xml = simplexml_load_file($mdFile);

    if (isset($federationConfig["cacheDuration"])) {
        $xml->addAttribute("cacheDuration", $federationConfig["cacheDuration"]);
    }

    render($xml->asXML(), filemtime($mdFile));
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

    $last_modified_time = 0;
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

            $entity_last_modified_time = filemtime($file);
            if ($entity_last_modified_time > $last_modified_time) {
                $last_modified_time = $entity_last_modified_time;
            }
        }
    }

    render($doc->saveXML(), $last_modified_time);
}
