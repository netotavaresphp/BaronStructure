<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/helpers.php';

date_default_timezone_set((require base_path('config/app.php'))['timezone'] ?? 'UTC');

use Core\Container\Container;
use Core\Routing\Router;
use Core\View\View;

$container = new Container();

$container->set(View::class, fn() => new View((require base_path('config/paths.php'))['views']));
$container->set(Router::class, fn(Container $c) => new Router($c));

/** @var Router $router */
$router = $container->get(Router::class);

// Carrega rotas
require base_path('routes/web.php');

return [$container, $router];
