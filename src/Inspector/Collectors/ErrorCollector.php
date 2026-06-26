<?php

namespace Helix\Inspector\Collectors;

use Helix\Inspector\CollectorInterface;

class ErrorCollector implements CollectorInterface
{
    private static array $errors = [];

    public function name(): string  { return 'errors'; }
    public function title(): string { return 'Errors'; }
    public function icon(): string  { return '⚠️'; }

    public function boot(): void
    {
        $previous = set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use (&$previous): bool {
            if (!(error_reporting() & $errno)) {
                return false;
            }

            self::$errors[] = [
                'type'    => $this->typeName($errno),
                'level'   => $this->level($errno),
                'message' => $errstr,
                'file'    => $this->shortPath($errfile),
                'line'    => $errline,
            ];

            // Chain to previous handler if one existed
            if ($previous) {
                return (bool) ($previous)($errno, $errstr, $errfile, $errline);
            }

            return false;
        });

        register_shutdown_function(function (): void {
            $e = error_get_last();
            if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::$errors[] = [
                    'type'    => $this->typeName($e['type']),
                    'level'   => 'fatal',
                    'message' => $e['message'],
                    'file'    => $this->shortPath($e['file']),
                    'line'    => $e['line'],
                ];
            }
        });
    }

    public function collect(): array
    {
        $counts = ['fatal' => 0, 'error' => 0, 'warning' => 0, 'notice' => 0, 'deprecated' => 0];

        foreach (self::$errors as $e) {
            $lvl = $e['level'] ?? 'error';
            if (isset($counts[$lvl])) {
                $counts[$lvl]++;
            }
        }

        return [
            'total'  => count(self::$errors),
            'counts' => $counts,
            'errors' => array_slice(self::$errors, 0, 100),
        ];
    }

    private function typeName(int $errno): string
    {
        $map = [
            E_ERROR             => 'E_ERROR',
            E_WARNING           => 'E_WARNING',
            E_NOTICE            => 'E_NOTICE',
            E_DEPRECATED        => 'E_DEPRECATED',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_STRICT            => 'E_STRICT',
            E_PARSE             => 'E_PARSE',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
        ];

        return $map[$errno] ?? 'E_UNKNOWN';
    }

    private function level(int $errno): string
    {
        if (in_array($errno, [E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return 'error';
        }
        if (in_array($errno, [E_WARNING, E_USER_WARNING], true)) {
            return 'warning';
        }
        if (in_array($errno, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
            return 'deprecated';
        }
        return 'notice';
    }

    private function shortPath(string $path): string
    {
        $base = defined('ABSPATH') ? ABSPATH : '';
        if (!$base) return $path;

        $normPath = str_replace('\\', '/', $path);
        $normBase = str_replace('\\', '/', $base);

        if (str_starts_with($normPath, $normBase)) {
            return substr($normPath, strlen($normBase));
        }

        return $normPath;
    }
}
