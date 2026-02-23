<?php

function base_path(string $path = ''): string
{
    $root = dirname(__DIR__);
    return $path ? $root . '/' . ltrim($path, '/') : $root;
}

function dd(...$vars): void
{
    foreach ($vars as $v) {
        var_dump($v);
    }
    exit(1);
}
