<?php

namespace Helix\Config;

class Config
{
    protected static array $items = [];

    public static function load(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (glob($path . '/*.php') as $file) {
            $key  = basename($file, '.php');
            $data = require $file;

            if (is_array($data)) {
                static::$items[$key] = $data;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $data     = static::$items;

        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $data     = &static::$items;

        foreach ($segments as $i => $segment) {
            if ($i === array_key_last($segments)) {
                $data[$segment] = $value;
                return;
            }
            if (!isset($data[$segment]) || !is_array($data[$segment])) {
                $data[$segment] = [];
            }
            $data = &$data[$segment];
        }
    }

    public static function has(string $key): bool
    {
        $sentinel = '__HELIX_NOT_FOUND__';
        return static::get($key, $sentinel) !== $sentinel;
    }

    public static function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }

    public static function all(): array
    {
        return static::$items;
    }
}
