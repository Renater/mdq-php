<?php

use Monolog\Logger;

$config["debug"] = false;

// Logging
$config["logging"] = [
  "logFile" => "/var/log/mdq-php/mdq-php.log",
  "logLevel" => Monolog\Logger::DEBUG
];
// metadata sources
$config["federations"]["test"] = [
  "localPath"     => "/var/cache/shibboleth/test",
  "cacheDuration" => "PT1H",
];
