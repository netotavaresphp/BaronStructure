<?php

declare(strict_types=1);

namespace Core\Process;

use RuntimeException;

final class Runner
{
    /**
     * Executa um comando no SO.
     * Retorna: ['exit_code' => int, 'stdout' => string, 'stderr' => string]
     */
    public function run(array $command, ?string $cwd = null, array $env = []): array
    {
        // Escapa tudo com segurança
        $cmd = implode(' ', array_map('escapeshellarg', $command));

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $cwd, $env ?: null);

        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start process.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exit_code' => (int)$exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }

    public function runJar(string $jarPath, array $args = [], ?string $cwd = null): array
    {
        $command = array_merge(['java', '-jar', $jarPath], $args);
        return $this->run($command, $cwd);
    }
}
