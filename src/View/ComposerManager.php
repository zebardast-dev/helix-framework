<?php

namespace Helix\View;

use Throwable;

class ComposerManager
{
    protected static array $composers = [];

    public static function composer($views, $callback): void
    {
        foreach ((array) $views as $view) {

            if (!is_string($view) || trim($view) === '') {
                continue;
            }

            static::$composers[$view][] = $callback;
        }
    }

    public static function clear(): void
    {
        static::$composers = [];
    }

    public static function run(string $view, array $data = []): array
    {
        foreach (static::$composers as $pattern => $callbacks) {

            if (!static::matches($pattern, $view)) {
                continue;
            }

            foreach ($callbacks as $callback) {

                try {
                    $instance = static::resolve($callback);

                    if (!$instance || !method_exists($instance, 'compose')) {
                        continue;
                    }

                    $result = $instance->compose($view, $data);

                    if (is_array($result)) {
                        $data = array_merge($data, $result);
                    }

                } catch (Throwable $e) {
                    continue;
                }
            }
        }

        return $data;
    }

    protected static function resolve($callback): ?object
    {
        if (is_object($callback)) {
            return $callback;
        }

        if (is_string($callback) && class_exists($callback)) {
            return app()->make($callback);
        }

        return null;
    }

    protected static function matches(string $pattern, string $view): bool
    {
        $regex = str_replace('\*', '.*', preg_quote($pattern, '/'));

        return (bool) preg_match("/^{$regex}$/", $view);
    }
}
