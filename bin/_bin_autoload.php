<?php

use Soatok\Website\Engine\Exceptions\BaseException;
use Soatok\Website\Engine\GlobalConfig;

define('SOATOK_ROOT', \dirname(__DIR__));
define('SOATOK_WEB_ROOT', __DIR__);

require_once SOATOK_ROOT . '/vendor/autoload.php';

try {
    $config = GlobalConfig::instance();
} catch (BaseException $ex) {
    echo $ex->getMessage(), PHP_EOL;
    echo $ex->getTraceAsString(), PHP_EOL;
    exit(1);
}
