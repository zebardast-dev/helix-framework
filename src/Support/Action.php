<?php

namespace Helix\Support;

class Action
{
    public static function add(string $hook, callable $callback, int $priority = 10): void
    {
        add_action($hook, $callback, $priority, static::argCount($callback));
    }

    public static function remove(string $hook, callable $callback, int $priority = 10): void
    {
        remove_action($hook, $callback, $priority);
    }

    public static function do(string $hook, mixed ...$args): void
    {
        do_action($hook, ...$args);
    }

    public static function has(string $hook, callable|false $callback = false): bool|int
    {
        return has_action($hook, $callback);
    }

    protected static function argCount(callable $callback): int
    {
        try {
            $ref = is_array($callback)
                ? new \ReflectionMethod($callback[0], $callback[1])
                : new \ReflectionFunction(\Closure::fromCallable($callback));

            return max(1, $ref->getNumberOfParameters());
        } catch (\ReflectionException) {
            return 1;
        }
    }
}
