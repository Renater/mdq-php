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

function get_sha1_id($id) {

    if (preg_match('/^\{sha1\}([0-9a-f]{40})$/', $id, $matches)) {
        return $matches[1];
    } else {
        return sha1($id);
    }

}
