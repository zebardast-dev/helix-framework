<?php

namespace Helix\Foundation;

use Illuminate\Container\Container;

class Application extends Container
{
    protected string $namespace = 'App\\';
    protected string $basePath;
    protected PathResolver $pathResolver;

    public function __construct(string $basePath = '')
    {
        $this->basePath     = rtrim($basePath, '/\\');
        $this->pathResolver = new PathResolver($this->basePath);
    }

    public function basePath(string $path = ''): string
    {
        return $path
            ? $this->basePath . '/' . ltrim($path, '/')
            : $this->basePath;
    }

    public function paths(): PathResolver
    {
        return $this->pathResolver;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }
}
