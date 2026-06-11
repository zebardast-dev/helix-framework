<?php

namespace Helix\Foundation;

use Illuminate\Container\Container;

class Application extends Container
{
    protected string $namespace = 'App\\';

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }
}
