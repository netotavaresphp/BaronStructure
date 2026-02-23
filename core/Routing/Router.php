<?php

declare(strict_types=1);

namespace Core\Routing;

use Core\Container\Container;
use Core\Exceptions\NotFoundException;
use Core\Http\Request;
use Core\Http\Response;

final class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string}>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => [],
    ];

    public function __construct(private Container $container) {}

    public function get(string $path, array $handler): void
    {
        $this->map('GET', $path, $handler);
    }
    public function post(string $path, array $handler): void
    {
        $this->map('POST', $path, $handler);
    }

    public function map(string $method, string $path, array $handler): void
    {
        $method = strtoupper($method);
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $methodRoutes = $this->routes[$request->method] ?? [];
        $handler = $methodRoutes[$request->path] ?? null;

        if (!$handler) {
            throw new NotFoundException("Route not found: {$request->method} {$request->path}");
        }

        [$class, $action] = $handler;

        $controller = $this->container->get($class);

        if (!method_exists($controller, $action)) {
            throw new NotFoundException("Action not found: {$class}::{$action}");
        }

        $result = $controller->{$action}($request);

        if (!$result instanceof Response) {
            // Força contrato: action deve retornar Response
            return Response::html((string)$result);
        }

        return $result;
    }
}
