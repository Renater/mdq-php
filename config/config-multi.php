<?php

/*
 * This sample config file shows how to configure for a composed endpoint, gathering 2 or more federations, whose entities are located in distinct folders
 */
use Monolog\Logger;

$config["debug"] = false;

// Logging
$config["logging"] = [
  "logFile" => "/var/log/mdq-php/mdq-php.log",
  "logLevel" => Monolog\Logger::DEBUG
];
$config["federations"]["fed1"] = [
  "name" => "fed1",
  "localPath" => "/var/cache/shibboleth/fed1"
];
$config["federations"]["fed2"] = [
  "name" => "fed2",
  "localPath" => "/var/cache/shibboleth/fed2"
];
