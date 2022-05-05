<?php

function extractEntityID($pathInfo)
{
    global $logger;
    $logger->debug("Extracting entityID from ".$pathInfo);
    $index = strpos($pathInfo, "/entities/");
    return urldecode(substr($_SERVER['PATH_INFO'], $index + 10));
}

function endsWith($string, $endString)
{
    $len = strlen($endString);
    if ($len == 0) {
        return true;
    }
    return (substr($string, -$len) === $endString);
}

function startsWith($string, $startString)
{
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}

/**
 * Given a User-Agent, try to determine whether it is a SAML Entity
 * The goal is to use this info to allow client no using
 * application/samlmetadata+xml Accept header
 */
function isSamlEntity($userAgent)
{
    global $config;
    foreach ($config["saml_entities_regex"] as $regex) {
        if (preg_match($regex, $userAgent)) {
            return true;
        }
    }
    return false;
}
