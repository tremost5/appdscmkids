<?php

use App\Compat\CodeIgniter\HTTP\CiRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

if (!defined('FCPATH')) {
    define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
}

if (!function_exists('base_url')) {
    function base_url(?string $path = null): string
    {
        $path = $path ?? '';
        return $path === '' ? url('/') : url($path);
    }
}

if (!function_exists('site_url')) {
    function site_url(?string $path = null): string
    {
        return base_url($path);
    }
}

if (!function_exists('esc')) {
    function esc(mixed $value): string
    {
        return e((string) $value);
    }
}

if (!function_exists('csrf_hash')) {
    function csrf_hash(): string
    {
        return (string) (csrf_token() ?? '');
    }
}

if (!function_exists('uri_string')) {
    function uri_string(): string
    {
        return trim((string) request()->path(), '/');
    }
}

if (!function_exists('url_is')) {
    function url_is(string $pattern): bool
    {
        $path = trim((string) request()->path(), '/');
        $pattern = trim($pattern, '/');

        if (str_ends_with($pattern, '*')) {
            $prefix = rtrim(substr($pattern, 0, -1), '/');
            return $prefix === '' ? true : str_starts_with($path, $prefix);
        }

        return $path === $pattern;
    }
}

if (!function_exists('helper')) {
    function helper(array|string $names): void
    {
        $names = is_array($names) ? $names : [$names];
        foreach ($names as $name) {
            $normalized = trim((string) $name);
            if ($normalized === '') {
                continue;
            }

            $candidates = [
                app_path('Helpers/' . $normalized . '_helper.php'),
                app_path('Helpers/' . $normalized . '.php'),
            ];

            foreach ($candidates as $file) {
                if (is_file($file)) {
                    require_once $file;
                    break;
                }
            }
        }
    }
}

if (!function_exists('model')) {
    function model(string $class): mixed
    {
        $fqcn = str_contains($class, '\\') ? $class : 'App\\Models\\' . $class;
        return app($fqcn);
    }
}

if (!function_exists('log_message')) {
    function log_message(string $level, string $message, array $context = []): void
    {
        $rendered = preg_replace_callback('/\{([^}]+)\}/', static function ($matches) use ($context) {
            $key = $matches[1] ?? '';
            return array_key_exists($key, $context) ? (string) $context[$key] : $matches[0];
        }, $message) ?? $message;

        Log::log($level, $rendered, $context);
    }
}

if (!function_exists('service')) {
    function service(string $name): mixed
    {
        return match ($name) {
            'request' => new CiRequest(request()),
            default => app($name),
        };
    }
}

// Load helper functions globally for CI4-style direct calls in controllers/views.
$helpersDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR;
foreach (glob($helpersDir . '*.php') ?: [] as $helperFile) {
    if (is_file($helperFile)) {
        require_once $helperFile;
    }
}
