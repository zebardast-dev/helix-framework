<?php

namespace Helix\View;

use Illuminate\View\Compilers\BladeCompiler;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ComponentDiscovery
{
    public static function register(): void
    {
        $blade      = app(BladeCompiler::class);
        $components = static::loadComponents();

        foreach ($components as $alias => $class) {
            if (class_exists($class)) {
                $blade->component($alias, $class);
            }
        }
    }

    protected static function loadComponents(): array
    {
        $cache = static::cachePath();

        if (file_exists($cache)) {
            return require $cache;
        }

        $components = static::discover();
        static::writeCache($components);

        return $components;
    }

    protected static function discover(): array
    {
        $path      = app('helix.components_path');
        $namespace = app('helix.components_namespace');
        $components = [];

        if (!is_dir($path)) {
            return $components;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace($path . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $class    = $namespace . str_replace(['/', '.php'], ['\\', ''], $relative);
            $alias    = static::generateAlias($relative);

            $components[$alias] = $class;
        }

        return $components;
    }

    protected static function writeCache(array $components): void
    {
        $cache = static::cachePath();

        if (!is_dir(dirname($cache))) {
            mkdir(dirname($cache), 0755, true);
        }

        file_put_contents($cache, '<?php return ' . var_export($components, true) . ';');
    }

    protected static function cachePath(): string
    {
        return app('helix.cache_path') . '/components.php';
    }

    protected static function generateAlias(string $path): string
    {
        $name     = str_replace('.php', '', $path);
        $name     = str_replace('/', '.', $name);
        $segments = explode('.', $name);

        $segments = array_map(
            fn($s) => strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $s)),
            $segments
        );

        return implode('.', $segments);
    }

    public static function clearCache(): void
    {
        $cache = static::cachePath();

        if (file_exists($cache)) {
            unlink($cache);
        }
    }
}
