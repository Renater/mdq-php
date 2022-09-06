<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

require_once(__DIR__ . '/../vendor/autoload.php');
#require_once "phar://mdq-php.phar/vendor/autoload.php";

if (isset($_SERVER['MDQ_CONFIG'])) {
    require_once($_SERVER['MDQ_CONFIG']);
} else {
    // require_once($topLevelDir . '/etc/mdq-php/config.php');
    require_once('../config/config.php');
}

require_once(__DIR__ . '/../lib/functions.php');

// Config init code
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('zlib.output_compression', true);

$formatter = new LineFormatter();
$formatter->ignoreEmptyContextAndExtra(true);

$stream = new StreamHandler($config['logging']['logFile'], $config['logging']['logLevel']);
$stream->setFormatter($formatter);

$logger = new Logger('mdq');
$logger->pushHandler($stream);
