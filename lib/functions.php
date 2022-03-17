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
