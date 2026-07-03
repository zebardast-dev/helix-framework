<?php

namespace Helix\Foundation;

class PathResolver
{
    public function __construct(protected string $basePath) {}

    public function base(string $path = ''): string
    {
        return $this->join($this->basePath, $path);
    }

    public function app(string $path = ''): string
    {
        return $this->join($this->basePath . '/app', $path);
    }

    public function config(string $path = ''): string
    {
        return $this->join($this->basePath . '/config', $path);
    }

    public function resources(string $path = ''): string
    {
        return $this->join($this->basePath . '/resources', $path);
    }

    public function views(string $path = ''): string
    {
        return $this->join($this->basePath . '/resources/views', $path);
    }

    public function storage(string $path = ''): string
    {
        return $this->join($this->basePath . '/storage', $path);
    }

    public function cache(string $path = ''): string
    {
        return $this->join($this->basePath . '/storage/cache', $path);
    }

    public function viewCache(string $path = ''): string
    {
        return $this->join($this->basePath . '/storage/cache/views', $path);
    }

    public function public(string $path = ''): string
    {
        return $this->join($this->basePath . '/public', $path);
    }

    private function join(string $base, string $path): string
    {
        return $path ? $base . '/' . ltrim($path, '/') : $base;
    }
}
