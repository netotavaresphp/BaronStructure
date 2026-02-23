<?php

declare(strict_types=1);

namespace Core\Http;

final class Response
{
    public function __construct(
        private string $body,
        private int $status = 200,
        private array $headers = [],
    ) {}

    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $k => $v) {
            header($k . ': ' . $v);
        }
        echo $this->body;
    }
}
