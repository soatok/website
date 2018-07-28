<?php
declare(strict_types=1);
namespace Soatok\Website;

use FastRoute\RouteCollector;
use Soatok\Website\RequestHandler\{
    DefaultPage,
    StaticPage
};
use Soatok\Website\RequestHandler\Den\{
    Dashboard,
    Login,
    Logout,
    MyAccount,
    RecoverAccount,
    Register
};

/* This script must return a callable */
return function(RouteCollector $r) {
    // $r->addRoute('GET', '/blog/{slug:[a-z0-9\-]+?}', BlogPost::class);
    $r->addRoute(['GET', 'POST'], '/den/signup', Register::class);
    $r->addRoute(['GET', 'POST'], '/den/login', Login::class);
    $r->addRoute('GET', '/den/logout/{nonce:[A-Za-z0-9\_\-]+?}', Logout::class);
    $r->addRoute('GET', '/den/account-recovery/{token}', RecoverAccount::class);
    $r->addRoute(['GET', 'POST'], '/den/account-recovery', RecoverAccount::class);
    $r->addRoute(['GET', 'POST'], '/den/my-account', MyAccount::class);
    $r->addRoute('GET', '/den', Dashboard::class);

    $r->addRoute('GET', '/{name:[a-zA-Z0-9\-_\/]+?}', StaticPage::class);
    $r->addRoute('GET', '/', DefaultPage::class);
    $r->addRoute('GET', '', DefaultPage::class);
};
