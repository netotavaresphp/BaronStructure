<?php

declare(strict_types=1);

namespace Core\Container;

use ReflectionClass;
use RuntimeException;

final class Container
{
    /** @var array<string, callable> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->bindings[$id])) {
            $factory = $this->bindings[$id];
            $obj = $factory($this);
            return $this->instances[$id] = $obj;
        }

        // Auto-wiring básico: resolve construtor por type-hint
        if (!class_exists($id)) {
            throw new RuntimeException("Container: class not found: {$id}");
        }

        $ref = new ReflectionClass($id);
        $ctor = $ref->getConstructor();

        if (!$ctor || $ctor->getNumberOfParameters() === 0) {
            return $this->instances[$id] = new $id();
        }

        $args = [];
        foreach ($ctor->getParameters() as $p) {
            $t = $p->getType();
            if (!$t || $t->isBuiltin()) {
                throw new RuntimeException("Container: cannot autowire param \${$p->getName()} of {$id}");
            }
            $args[] = $this->get($t->getName());
        }

        return $this->instances[$id] = $ref->newInstanceArgs($args);
    }
}
