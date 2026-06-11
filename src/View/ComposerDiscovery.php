<?php

namespace Helix\View;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ComposerDiscovery
{
    protected string $path;
    protected string $namespace;

    public function __construct(?string $path = null, ?string $namespace = null)
    {
        $this->path      = $path      ?? app('helix.composers_path');
        $this->namespace = $namespace ?? app('helix.composers_namespace');
    }

    public function register(): void
    {
        foreach ($this->loadCache() as $views => $class) {

            if (!class_exists($class)) {
                continue;
            }

            ComposerManager::composer(explode(',', $views), $class);
        }
    }

    protected function loadCache(): array
    {
        $cache = $this->cachePath();

        if (file_exists($cache)) {
            return require $cache;
        }

        $map = $this->discover();
        $this->writeCache($map);

        return $map;
    }

    protected function discover(): array
    {
        if (!is_dir($this->path)) {
            return [];
        }

        $map      = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path)
        );

        foreach ($iterator as $file) {

            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $class = $this->namespace . $file->getBasename('.php');

            if (!class_exists($class)) {
                continue;
            }

            $instance = app($class);

            if (!method_exists($instance, 'views')) {
                continue;
            }

            $views = (array) $instance->views();

            if (empty($views)) {
                continue;
            }

            $map[implode(',', $views)] = $class;
        }

        return $map;
    }

    protected function writeCache(array $map): void
    {
        file_put_contents(
            $this->cachePath(),
            '<?php return ' . var_export($map, true) . ';'
        );
    }

    protected function cachePath(): string
    {
        return app('helix.cache_path') . '/composers.php';
    }
}
