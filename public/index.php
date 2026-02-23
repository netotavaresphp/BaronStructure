<?php

declare(strict_types=1);

use Core\Http\Request;
use Core\Exceptions\NotFoundException;

[$container, $router] = require dirname(__DIR__) . '/bootstrap/app.php';

$request = Request::fromGlobals();

try {
    $response = $router->dispatch($request);
} catch (NotFoundException $e) {
    http_response_code(404);
    echo "404 - Not Found";
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo "500 - Internal Server Error";
    exit;
}

$response->send();
