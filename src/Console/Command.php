<?php

namespace Helix\Console;

abstract class Command
{
    protected string $signature   = '';
    protected string $description = '';
    protected string $synopsis    = '';

    protected array $args    = [];
    protected array $options = [];

    public function __construct(array $argv)
    {
        foreach (array_slice($argv, 2) as $item) {
            if (str_starts_with($item, '--')) {
                [$key, $val]          = explode('=', ltrim($item, '--'), 2) + [1 => true];
                $this->options[$key]  = $val;
            } else {
                $this->args[] = $item;
            }
        }
    }

    abstract public function handle(): int;

    public function getSignature(): string   { return $this->signature; }
    public function getDescription(): string { return $this->description; }
    public function getSynopsis(): string    { return $this->synopsis; }

    // ── Output ──────────────────────────────────────────────────────────────

    protected function line(string $text = ''): void   { echo $text . PHP_EOL; }
    protected function newLine(): void                 { echo PHP_EOL; }

    protected function info(string $text): void
    {
        echo $this->cyan($text) . PHP_EOL;
    }

    protected function success(string $text): void
    {
        echo $this->green('  ✓ ' . $text) . PHP_EOL;
    }

    protected function error(string $text): void
    {
        fwrite(STDERR, $this->red('  ✗ ' . $text) . PHP_EOL);
    }

    protected function warn(string $text): void
    {
        echo $this->yellow('  ⚠ ' . $text) . PHP_EOL;
    }

    protected function comment(string $text): void
    {
        echo $this->dim('  ' . $text) . PHP_EOL;
    }

    // ── Color ────────────────────────────────────────────────────────────────

    protected function cyan(string $t): string   { return "\033[36m{$t}\033[0m"; }
    protected function green(string $t): string  { return "\033[32m{$t}\033[0m"; }
    protected function yellow(string $t): string { return "\033[33m{$t}\033[0m"; }
    protected function red(string $t): string    { return "\033[31m{$t}\033[0m"; }
    protected function dim(string $t): string    { return "\033[2m{$t}\033[0m"; }
    protected function bold(string $t): string   { return "\033[1m{$t}\033[0m"; }

    // ── Args ─────────────────────────────────────────────────────────────────

    protected function arg(int $index, mixed $default = null): mixed
    {
        return $this->args[$index] ?? $default;
    }

    protected function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    // ── File helpers ─────────────────────────────────────────────────────────

    protected function ensureDir(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    protected function writeFile(string $path, string $content): bool
    {
        if (file_exists($path)) {
            $this->error("File already exists: {$path}");
            return false;
        }

        file_put_contents($path, $content);
        return true;
    }

    protected function toKebab(string $name): string
    {
        return strtolower(preg_replace('/[A-Z]/', '-$0', lcfirst($name)));
    }

    protected function toSnake(string $name): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($name)));
    }
}
