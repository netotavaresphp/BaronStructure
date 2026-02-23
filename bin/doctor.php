<?php

declare(strict_types=1);

/**
 * Doctor (Strict): valida estrutura + convenções + regras de arquitetura.
 *
 * Uso:
 *   php bin/doctor.php
 *
 * Exit codes:
 *   0 = OK
 *   1 = warnings/errors
 */

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Doctor: failed to resolve project root.\n");
    exit(1);
}

function ok(string $msg): void
{
    fwrite(STDOUT, "[OK]   {$msg}\n");
}
function warn(string $msg): void
{
    fwrite(STDOUT, "[WARN] {$msg}\n");
}
function err(string $msg): void
{
    fwrite(STDERR, "[ERR]  {$msg}\n");
}

$errors = 0;
$warnings = 0;

function rel(string $root, string $abs): string
{
    return str_replace($root . DIRECTORY_SEPARATOR, '', $abs);
}

function normalizePath(string $p): string
{
    return str_replace('\\', '/', $p);
}

function startsWith(string $s, string $prefix): bool
{
    return strncmp($s, $prefix, strlen($prefix)) === 0;
}

function expectPath(string $root, string $path, string $type = 'any'): bool
{
    $full = $root . '/' . $path;
    if ($type === 'dir') return is_dir($full);
    if ($type === 'file') return is_file($full);
    return file_exists($full);
}

function scanPhpFiles(string $dir): array
{
    $files = [];
    if (!is_dir($dir)) return $files;

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($it as $f) {
        if ($f->isFile() && strtolower($f->getExtension()) === 'php') {
            $files[] = $f->getPathname();
        }
    }
    return $files;
}

function isViewFile(string $path): bool
{
    return (bool)preg_match('~[/\\\\]app[/\\\\]Views[/\\\\]~', $path);
}

function readFileSafe(string $file): string
{
    $c = file_get_contents($file);
    return $c === false ? '' : $c;
}

/**
 * Extrai a declaração principal (primeira) do arquivo:
 * - namespace
 * - tipo (class|interface|trait|enum)
 * - nome
 * - qualifier (final|abstract)
 */
function parsePrimaryDeclaration(string $content): array
{
    $ns = null;
    if (preg_match('/^\s*namespace\s+([^;]+);/m', $content, $m)) {
        $ns = trim($m[1]);
    }

    $decl = null;

    if (preg_match('/^\s*(final\s+|abstract\s+)?(class|interface|trait|enum)\s+([A-Za-z][A-Za-z0-9_]*)/m', $content, $m)) {
        $decl = [
            'qualifier' => trim((string)$m[1]),
            'type' => $m[2],
            'name' => $m[3],
        ];
    }

    return ['namespace' => $ns, 'decl' => $decl];
}

/**
 * Esperado:
 * - App\Foo\Bar => app/Foo/Bar.php
 * - Core\X\Y    => core/X/Y.php
 */
function expectedNamespaceFromPath(string $root, string $file): ?string
{
    $r = normalizePath(rel($root, $file));

    if (startsWith($r, 'app/')) {
        $sub = substr($r, 4);
        $sub = preg_replace('~\.php$~', '', $sub);
        $parts = array_values(array_filter(explode('/', $sub)));
        array_pop($parts);
        return 'App' . (count($parts) ? '\\' . implode('\\', $parts) : '');
    }

    if (startsWith($r, 'core/')) {
        $sub = substr($r, 5);
        $sub = preg_replace('~\.php$~', '', $sub);
        $parts = array_values(array_filter(explode('/', $sub)));
        array_pop($parts);
        return 'Core' . (count($parts) ? '\\' . implode('\\', $parts) : '');
    }

    return null;
}

function isPascalCase(string $name): bool
{
    return (bool)preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name);
}

function isCamelCase(string $name): bool
{
    return (bool)preg_match('/^[a-z][a-zA-Z0-9]*$/', $name);
}

function isUpperSnake(string $name): bool
{
    return (bool)preg_match('/^[A-Z][A-Z0-9]*(?:_[A-Z0-9]+)*$/', $name);
}

function isProbablyMethodName(string $name): bool
{
    if ($name === '__construct') return false;
    if (startsWith($name, '__')) return false;
    return true;
}

// -----------------------------
// MAIN
// -----------------------------

fwrite(STDOUT, "Doctor running at: {$root}\n\n");

// 1) Estrutura mínima
$requiredDirs = ['public', 'app', 'core', 'routes', 'config', 'bootstrap', 'bin'];
$requiredFiles = ['composer.json', 'public/index.php', 'bootstrap/app.php', 'routes/web.php'];

foreach ($requiredDirs as $d) {
    if (!expectPath($root, $d, 'dir')) {
        err("Missing dir: {$d}");
        $GLOBALS['errors']++;
    } else ok("Dir exists: {$d}");
}
foreach ($requiredFiles as $f) {
    if (!expectPath($root, $f, 'file')) {
        err("Missing file: {$f}");
        $GLOBALS['errors']++;
    } else ok("File exists: {$f}");
}

// 2) Composer PSR-4 esperado
$composer = $root . '/composer.json';
$composerJson = json_decode(readFileSafe($composer), true);

if (!is_array($composerJson)) {
    err("composer.json inválido ou não parseável.");
    $errors++;
} else {
    $psr4 = $composerJson['autoload']['psr-4'] ?? [];
    $need = ['App\\' => 'app/', 'Core\\' => 'core/'];
    foreach ($need as $ns => $dir) {
        if (($psr4[$ns] ?? null) !== $dir) {
            err("PSR-4 esperado {$ns} => {$dir} (atual: " . (($psr4[$ns] ?? 'null')) . ")");
            $errors++;
        } else {
            ok("PSR-4 ok: {$ns} => {$dir}");
        }
    }
}

// 3) vendor/autoload.php
if (!expectPath($root, 'vendor/autoload.php', 'file')) {
    warn("vendor/autoload.php não encontrado. Rode: composer install && composer dump-autoload");
    $warnings++;
} else {
    ok("Composer autoload presente (vendor/autoload.php).");
}

// 4) Regras de arquitetura (heurísticas)
$rules = [
    'Controllers não devem usar require/include (Views via View::render)' => [
        'path' => 'app/Controllers',
        'pattern' => '/\brequire(_once)?\b|\binclude(_once)?\b/',
        'severity' => 'warn',
    ],
    'Controllers não devem instanciar PDO diretamente' => [
        'path' => 'app/Controllers',
        'pattern' => '/new\s+PDO\s*\(/',
        'severity' => 'warn',
    ],
    'Models não devem depender de HTTP (Core\\Http\\Request/Response)' => [
        'path' => 'app/Models',
        'pattern' => '/Core\\\\Http\\\\(Request|Response)/',
        'severity' => 'err',
    ],
];

foreach ($rules as $label => $r) {
    $dir = $root . '/' . $r['path'];
    $hit = 0;

    foreach (scanPhpFiles($dir) as $file) {
        $content = readFileSafe($file);
        if ($content !== '' && preg_match($r['pattern'], $content)) {
            $hit++;
            $msg = "{$label} :: " . rel($root, $file);
            if ($r['severity'] === 'err') {
                err($msg);
                $errors++;
            } else {
                warn($msg);
                $warnings++;
            }
        }
    }

    if ($hit === 0) ok("Rule OK: {$label}");
}

// 5) Convenções: strict_types + naming + namespace + arquivo x classe
$scanRoots = [$root . '/app', $root . '/core'];

foreach ($scanRoots as $dir) {
    foreach (scanPhpFiles($dir) as $file) {
        if (isViewFile($file)) continue;

        $content = readFileSafe($file);

        // strict_types
        if (!preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $content)) {
            err("Missing declare(strict_types=1): " . rel($root, $file));
            $errors++;
        }

        $parsed = parsePrimaryDeclaration($content);
        $decl = $parsed['decl'];
        $ns = $parsed['namespace'];

        if ($decl === null) {
            warn("No class/interface/trait/enum found: " . rel($root, $file));
            $warnings++;
            continue;
        }

        $name = $decl['name'];

        // Classe em PascalCase (sem _)
        if (!isPascalCase($name)) {
            err("Declaration not in PascalCase: {$name} :: " . rel($root, $file));
            $errors++;
        }
        if (str_contains($name, '_')) {
            err("Underscore not allowed in declaration name: {$name} :: " . rel($root, $file));
            $errors++;
        }

        // Arquivo deve bater com nome da declaração
        $expectedFileName = pathinfo($file, PATHINFO_FILENAME);
        if ($expectedFileName !== $name) {
            err("Filename != declaration name: {$expectedFileName} != {$name} :: " . rel($root, $file));
            $errors++;
        }

        // Namespace deve bater com path (PSR-4)
        $expectedNs = expectedNamespaceFromPath($root, $file);
        if ($expectedNs !== null) {
            if ($ns === null) {
                err("Missing namespace ({$expectedNs}): " . rel($root, $file));
                $errors++;
            } elseif ($ns !== $expectedNs) {
                err("Namespace mismatch: {$ns} != {$expectedNs} :: " . rel($root, $file));
                $errors++;
            }
        }

        // Controllers: final + nome termina com Controller
        $isController = (bool)preg_match('~[/\\\\]app[/\\\\]Controllers[/\\\\]~', $file);
        if ($isController) {
            if (!str_ends_with($name, 'Controller')) {
                err("Controller class must end with 'Controller': {$name} :: " . rel($root, $file));
                $errors++;
            }
            $qual = trim($decl['qualifier'] ?? '');
            if ($qual !== 'final') {
                err("Controller must be final: {$name} :: " . rel($root, $file));
                $errors++;
            }
        }

        // Métodos/properties/const naming (heurística com regex)
        // Métodos
        if (preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $mm)) {
            foreach ($mm[1] as $mname) {
                if (!isProbablyMethodName($mname)) continue;
                if (!isCamelCase($mname)) {
                    err("Method not camelCase: {$mname} :: " . rel($root, $file));
                    $errors++;
                }
                if (str_contains($mname, '_')) {
                    err("Underscore not allowed in method name: {$mname} :: " . rel($root, $file));
                    $errors++;
                }
            }
        }

        // Propriedades
        if (preg_match_all('/\$(\w+)\s*[;=]/', $content, $pp)) {
            foreach ($pp[1] as $pname) {
                if (!isCamelCase($pname)) {
                    // pode pegar variáveis locais; por isso é warning, não error
                    warn("Possible non-camelCase variable/property: \${$pname} :: " . rel($root, $file));
                    $warnings++;
                }
                if (str_contains($pname, '_')) {
                    warn("Underscore found in variable/property: \${$pname} :: " . rel($root, $file));
                    $warnings++;
                }
            }
        }

        // Constantes
        if (preg_match_all('/const\s+([A-Za-z_][A-Za-z0-9_]*)\s*=/', $content, $cc)) {
            foreach ($cc[1] as $cname) {
                if (!isUpperSnake($cname)) {
                    err("Const not UPPER_SNAKE_CASE: {$cname} :: " . rel($root, $file));
                    $errors++;
                }
            }
        }
    }
}

// 6) Bootstrap smoke test (se vendor existe)
try {
    if (is_file($root . '/vendor/autoload.php')) {
        require $root . '/bootstrap/app.php';
        ok("bootstrap/app.php carregou sem exceção.");
    }
} catch (Throwable $e) {
    err("Falha ao carregar bootstrap/app.php: " . $e->getMessage());
    $errors++;
}

// 7) Summary
fwrite(STDOUT, "\n--- Doctor Summary ---\n");
fwrite(STDOUT, "Warnings: {$warnings}\n");
fwrite(STDOUT, "Errors:   {$errors}\n");

exit(($errors > 0) ? 1 : 0);
