<?php

namespace Helix\Inspector;

interface CollectorInterface
{
    public function name(): string;

    public function title(): string;

    public function icon(): string;

    public function boot(): void;

    public function collect(): array;
}
