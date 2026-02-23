<?php

use Core\Routing\Router;

$router->get('/', [App\Controllers\HomeController::class, 'index']);
