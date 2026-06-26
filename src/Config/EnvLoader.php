<?php

namespace Helix\Config;

class EnvLoader
{
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            if (strlen($value) > 1 && (
                ($value[0] === '"'  && $value[-1] === '"')  ||
                ($value[0] === "'"  && $value[-1] === "'")
            )) {
                $value = substr($value, 1, -1);
            } elseif ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
                // Strip inline comments from unquoted values
                $commentPos = strpos($value, ' #');
                if ($commentPos !== false) {
                    $value = rtrim(substr($value, 0, $commentPos));
                }
            }

            // Server/system env vars take priority
            if (getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key]    = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}
