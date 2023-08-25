<?php

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

function render($content, $last_modified_time)
{
    $etag = '"' . md5($content) . '"';
    if (
        (
            isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
            $_SERVER['HTTP_IF_NONE_MATCH'] == $etag
        ) ||
        (
            isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
            strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $last_modified_time
        )
    ) {
        http_response_code(304);
        exit();
    }

    header('Content-Type: application/samlmetadata+xml');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s ', $last_modified_time) . 'GMT');
    header('ETag: ' . $etag);
    http_response_code(200);
    echo $content;
}

function render_file($file)
{
    $last_modified_time = filemtime($file);
    $etag = '"' . md5($last_modified_time) . '"';
    if (
        (
            isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
            $_SERVER['HTTP_IF_NONE_MATCH'] == $etag
        ) ||
        (
            isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
            strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $last_modified_time
        )
    ) {
        http_response_code(304);
        exit();
    }

    header('Content-Type: application/samlmetadata+xml');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s ', $last_modified_time) . 'GMT');
    header('ETag: ' . $etag);
    http_response_code(200);
    readfile($file);
}
