<?php
declare(strict_types=1);
namespace Soatok\Website;

use FastRoute\RouteCollector;
use Soatok\Website\RequestHandler\{
    DefaultPage,
    StaticPage
};

/* This script must return a callable */
return function(RouteCollector $r) {
    $r->addRoute('GET', '/blog/{slug:[a-z0-9\-]+?}', BlogPost::class);
    $r->addRoute('GET', '/{name:[a-zA-Z0-9\-_\/]+?}', StaticPage::class);
    $r->addRoute('GET', '/', DefaultPage::class);
    $r->addRoute('GET', '', DefaultPage::class);
};
