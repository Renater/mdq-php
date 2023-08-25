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
        header("Location: /");
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
    $sources  = $params[1];
    $entityId = urldecode($params[3]);

    $logger->debug("Requested entity ID ". $entityId . " in sources " . $sources);

    // Look in each metadata source
    foreach (explode("+", $sources) as $source) {
        if (!isset($config["federations"][$source])) {
            $logger->error("Unknown source: $source");
            continue;
        }

        $path = sprintf("%s/entities/%s.xml", $config["federations"][$source]["localPath"], sha1($entityId));
        $result = file_exists($path);
        if ($result) {
            $logger->debug(sprintf("Checking %s: found", $path));
            $file = $path;
            break;
        } else {
            $logger->debug(sprintf("Checking %s: not found", $path));
        }
    }

    if (!$file) {
        http_response_code(404);
        exit("Unknown entityID " . $entityId);
    }

    render_file($file);
} else {
    // foo/entities
    $source = $params[1];

    $logger->debug("Requested all entities in source " . $source);

    if (strpos($source, '+')) {
        http_response_code(501);
        exit('Unsupported operation');
    }

    if (!isset($config["federations"][$source])) {
        $logger->error("Unknown source: $source");
        http_response_code(400);
        exit('Bad request');
    }

    $file = sprintf("%s/all.xml", $config["federations"][$source]["localPath"]);

    if (!file_exists($file)) {
        http_response_code(500);
        exit("Internal error");
    }

    render_file($file);
}
