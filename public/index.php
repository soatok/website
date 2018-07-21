<?php
declare(strict_types=1);

use Soatok\Website\Engine\Exceptions\BaseException;
use Soatok\Website\Engine\GlobalConfig;
use GuzzleHttp\Psr7\ServerRequest;

/**
 * Furified.com landing point
 *
 * This should be the only publicly accessible URI that executes code. All else
 * should be static resources.
 */

define('SOATOK_ROOT', \dirname(__DIR__));
define('SOATOK_WEB_ROOT', __DIR__);

require_once SOATOK_ROOT . '/vendor/autoload.php';

try {
    $config = GlobalConfig::instance();
} catch (BaseException $ex) {
    \header('Content-Type: text/plain;charset=UTF-8');
    echo $ex->getMessage(), PHP_EOL;
    exit(1);
}

try {
    $config->getRouter()
        ->serve(ServerRequest::fromGlobals());
} catch (Throwable $ex) {
    if ($config->isDebug()) {
        \header('Content-Type: text/plain;charset=UTF-8');
        echo 'Uncaught ', \get_class($ex), ': ', PHP_EOL;
        echo $ex->getMessage(), PHP_EOL;
        echo PHP_EOL;
        echo $ex->getFile(), PHP_EOL;
        echo 'Line ', $ex->getLine(), PHP_EOL;
        echo $ex->getTraceAsString();
    }
}
