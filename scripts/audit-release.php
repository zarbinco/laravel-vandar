<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$warnings = [];
$passes = [];

$pass = static function (string $message) use (&$passes): void {
    $passes[] = $message;
};

$fail = static function (string $message) use (&$failures): void {
    $failures[] = $message;
};

$warn = static function (string $message) use (&$warnings): void {
    $warnings[] = $message;
};

$path = static fn (string $path): string => $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

$composerPath = $path('composer.json');
if (! is_file($composerPath)) {
    $fail('composer.json exists');
} else {
    $composer = json_decode((string) file_get_contents($composerPath), true);

    if (! is_array($composer)) {
        $fail('composer.json is valid JSON');
    } elseif (($composer['name'] ?? null) !== 'zarbinco/laravel-vandar') {
        $fail('composer.json package name is zarbinco/laravel-vandar');
    } else {
        $pass('composer.json package name is zarbinco/laravel-vandar');
    }
}

foreach ([
    'config/vandar.php',
    'src/LaravelVandarServiceProvider.php',
    'README.md',
    'CHANGELOG.md',
    'CONTRIBUTING.md',
    'SECURITY.md',
    'LICENSE.md',
    'UPGRADE.md',
    'docs/endpoint-support.md',
    'docs/security.md',
    'docs/usage.md',
    'docs/release-checklist.md',
    'docs/production-checklist.md',
] as $requiredFile) {
    is_file($path($requiredFile))
        ? $pass("{$requiredFile} exists")
        : $fail("{$requiredFile} exists");
}

$internalNotesFile = 'REVIEW'.'_NOTES.md';
$trackedFiles = trackedFiles($root);

in_array($internalNotesFile, $trackedFiles, true)
    ? $fail('internal review artifact is not tracked')
    : $pass('internal review artifact is not tracked');

if (is_file($path($internalNotesFile))) {
    $warn('REVIEW_NOTES.md exists locally; it is allowed as an untracked maintainer note and export-ignored from release archives');
}

if ($trackedFiles !== []) {
    foreach ($trackedFiles as $trackedFile) {
        if (str_starts_with($trackedFile, 'vendor/')) {
            $fail('vendor/ is not committed');
        }

        if (str_starts_with($trackedFile, 'node_modules/')) {
            $fail('node_modules/ is not committed');
        }

        if ($trackedFile === '.env') {
            $fail('.env is not committed');
        }

        if ($trackedFile === '.phpunit.result.cache') {
            $fail('.phpunit.result.cache is not committed');
        }

        if ($trackedFile === 'composer.lock') {
            $fail('composer.lock is not committed for this library package');
        }
    }

    $pass('tracked files do not include vendor/, node_modules/, .env, .phpunit.result.cache, or composer.lock');
} else {
    is_file($path('.env')) ? $fail('.env is absent') : $pass('.env is absent');
}

if (is_dir($path('vendor'))) {
    $warn('vendor/ exists locally; it is allowed for development but excluded from release archives');
}

if (is_dir($path('node_modules'))) {
    $warn('node_modules/ exists locally; it must be excluded from release archives');
}

if (is_file($path('.phpunit.result.cache'))) {
    $warn('.phpunit.result.cache exists locally; it is excluded from release archives');
}

if (is_file($path('composer.lock'))) {
    $warn('composer.lock exists locally; it is excluded from release archives');
}

foreach (['.env', '.env.local', '.env.production'] as $envFile) {
    is_file($path($envFile))
        ? $fail("real-looking {$envFile} is absent from the package root")
        : $pass("{$envFile} is absent from the package root");
}

$scanFiles = scanFiles($root, [
    'src',
    'tests',
    'docs',
    'config',
    '.github',
    'README.md',
    'SECURITY.md',
    'CONTRIBUTING.md',
    'CODE_OF_CONDUCT.md',
    'composer.json',
]);

$patterns = [
    'webhook'.'.site URL' => '/webhook\.'.'site/i',
    'real-looking IBAN' => '/\bIR\d{24}\b/',
    '16-digit card number' => '/(?<!\d)\d{16}(?!\d)/',
    'Iranian mobile number' => '/\b09\d{9}\b/',
    'real-looking Vandar secret env value' => '/^VANDAR_(?:ACCESS_TOKEN|REFRESH_TOKEN|IPG_API_KEY)[^\S\r\n]*=[^\S\r\n]*(?!(?:$|#|fake|your|example|test|changeme|<))/im',
    'real-looking authorization bearer value' => '/\bAuthorization\s*:\s*Bearer\s+(?!fake|example|test|your|<)[A-Za-z0-9._~+\/-]{16,}/i',
];

foreach ($scanFiles as $file) {
    $contents = (string) file_get_contents($path($file));

    foreach ($patterns as $label => $pattern) {
        if (preg_match($pattern, $contents) === 1) {
            $fail("Potential {$label} found in {$file}");
        }
    }
}

$pass('source, docs, tests, config, and GitHub templates scanned for obvious sensitive patterns');

$repositoryFiles = scanFiles($root, ['.']);

foreach ($repositoryFiles as $file) {
    $contents = (string) file_get_contents($path($file));

    $oldNamespacePattern = '/'.'mrez'.'dev|Mrez'.'dev'.'/';

    if (preg_match($oldNamespacePattern, $contents) === 1) {
        $fail("Old namespace reference found in {$file}");
    }
}

$pass('old package namespace references are absent');

$forbiddenTerms = [
    'Cod'.'ex',
    'Chat'.'GPT',
    'A'.'I-generated',
    'generated by '.'A'.'I',
    'pro'.'mpt',
    'upload '.'here',
    'zip '.'patch',
    'review '.'notes',
    'Micro '.'Pha'.'se',
    'Pha'.'se '.'1',
    'Pha'.'se '.'2',
    'Pha'.'se '.'3',
    'Pha'.'se '.'4',
    'Pha'.'se '.'5',
    'Pha'.'se '.'6',
    'Pha'.'se '.'7',
    'Pha'.'se '.'8',
];

foreach ($repositoryFiles as $file) {
    $contents = (string) file_get_contents($path($file));

    foreach ($forbiddenTerms as $term) {
        if (stripos($contents, $term) !== false) {
            $fail("Forbidden internal term found in {$file}");
            break;
        }
    }
}

$publicDocumentationFiles = scanFiles($root, [
    'README.md',
    'CHANGELOG.md',
    'SECURITY.md',
    'CONTRIBUTING.md',
    'CODE_OF_CONDUCT.md',
    'docs',
    '.github',
    'composer.json',
]);

foreach ($publicDocumentationFiles as $file) {
    $contents = (string) file_get_contents($path($file));

    if (preg_match('/\b'.'pha'.'se\b/i', $contents) === 1) {
        $fail("Internal release-stage wording found in {$file}");
    }
}

$pass('public documentation is free of internal release workflow terms');

foreach ($passes as $message) {
    echo "[PASS] {$message}".PHP_EOL;
}

foreach ($warnings as $message) {
    echo "[WARN] {$message}".PHP_EOL;
}

if ($failures !== []) {
    foreach ($failures as $message) {
        echo "[ERROR] {$message}".PHP_EOL;
    }

    echo 'Release audit failed.'.PHP_EOL;
    exit(1);
}

echo 'Release audit passed.'.PHP_EOL;

/**
 * @return array<int, string>
 */
function trackedFiles(string $root): array
{
    $nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
    $command = 'git -C '.escapeshellarg($root).' ls-files 2> '.$nullDevice;
    exec($command, $output, $exitCode);

    if ($exitCode !== 0) {
        return [];
    }

    return array_values(array_filter(
        array_map(static fn (string $file): string => str_replace('\\', '/', trim($file)), $output),
        static fn (string $file): bool => $file !== '',
    ));
}

/**
 * @param  array<int, string>  $roots
 * @return array<int, string>
 */
function scanFiles(string $root, array $roots): array
{
    $files = [];
    $excludedDirectories = ['.git', 'vendor', 'node_modules', 'coverage', 'build', '.phpunit.cache', '.idea', '.vscode'];
    $excludedFiles = ['REVIEW_NOTES.md', 'composer.lock', '.phpunit.result.cache'];

    foreach ($roots as $scanRoot) {
        $absolute = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $scanRoot);

        if (is_file($absolute)) {
            $files[] = normalizePath(substr($absolute, strlen($root) + 1));

            continue;
        }

        if (! is_dir($absolute)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo instanceof SplFileInfo || ! $fileInfo->isFile()) {
                continue;
            }

            $relative = normalizePath(substr($fileInfo->getPathname(), strlen($root) + 1));
            $parts = explode('/', $relative);

            if (array_intersect($parts, $excludedDirectories) !== []) {
                continue;
            }

            if (str_ends_with($relative, '.zip') || in_array($relative, $excludedFiles, true)) {
                continue;
            }

            $files[] = $relative;
        }
    }

    return array_values(array_unique($files));
}

function normalizePath(string $path): string
{
    $normalized = str_replace('\\', '/', $path);

    return str_starts_with($normalized, './') ? substr($normalized, 2) : $normalized;
}
