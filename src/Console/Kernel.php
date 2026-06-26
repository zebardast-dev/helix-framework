<?php

namespace Helix\Console;

use Helix\Console\Commands\CacheClear;
use Helix\Console\Commands\CacheStatus;
use Helix\Console\Commands\DbSeed;
use Helix\Console\Commands\MakeCommand;
use Helix\Console\Commands\MakeComponent;
use Helix\Console\Commands\MakeComposer;
use Helix\Console\Commands\MakeHandler;
use Helix\Console\Commands\MakeModel;
use Helix\Console\Commands\MakePostType;
use Helix\Console\Commands\MakeRepository;
use Helix\Console\Commands\MakeSeeder;

class Kernel
{
    protected array $commands = [];
    protected array $argv;

    public function __construct(array $argv)
    {
        $this->argv = $argv;
        $this->bootDefaultCommands();
    }

    public function register(string $commandClass): void
    {
        $temp = new $commandClass([]);
        $this->commands[$temp->getSignature()] = $commandClass;
    }

    public function run(): int
    {
        $name = $this->argv[1] ?? null;

        if (!$name || $name === 'list') {
            $this->printBanner();
            return 0;
        }

        if (!isset($this->commands[$name])) {
            fwrite(STDERR, "\033[31m  Command not found:\033[0m {$name}" . PHP_EOL . PHP_EOL);
            $this->printBanner();
            return 1;
        }

        return (new $this->commands[$name]($this->argv))->handle();
    }

    public function register_many(array $classes): void
    {
        foreach ($classes as $class) {
            $this->register($class);
        }
    }

    protected function bootDefaultCommands(): void
    {
        $this->register(MakeComponent::class);
        $this->register(MakeComposer::class);
        $this->register(MakeHandler::class);
        $this->register(MakeModel::class);
        $this->register(MakeRepository::class);
        $this->register(MakePostType::class);
        $this->register(MakeSeeder::class);
        $this->register(MakeCommand::class);
        $this->register(DbSeed::class);
        $this->register(CacheClear::class);
        $this->register(CacheStatus::class);
    }

    protected function printBanner(): void
    {
        $r = "\033[0m";
        $logo   = "\033[1;36m";  // bold cyan
        $dim    = "\033[2m";
        $green  = "\033[32m";
        $yellow = "\033[33m";
        $cyan   = "\033[36m";

        echo PHP_EOL;
        echo "{$logo}  ██╗  ██╗███████╗██╗     ██╗██╗  ██╗{$r}" . PHP_EOL;
        echo "{$logo}  ██║  ██║██╔════╝██║     ██║╚██╗██╔╝{$r}" . PHP_EOL;
        echo "{$logo}  ███████║█████╗  ██║     ██║ ╚███╔╝ {$r}" . PHP_EOL;
        echo "{$logo}  ██╔══██║██╔══╝  ██║     ██║ ██╔██╗ {$r}" . PHP_EOL;
        echo "{$logo}  ██║  ██║███████╗███████╗██║██╔╝ ██╗{$r}" . PHP_EOL;
        echo "{$logo}  ╚═╝  ╚═╝╚══════╝╚══════╝╚═╝╚═╝  ╚═╝{$r}" . PHP_EOL;
        echo PHP_EOL;
        echo "  {$dim}WordPress Theme Framework{$r}  {$green}" . $this->version() . "{$r}" . PHP_EOL;
        echo PHP_EOL;
        echo "  {$yellow}Usage:{$r}" . PHP_EOL;
        echo "    {$cyan}php helix{$r} <command> {$dim}[arguments]{$r}" . PHP_EOL;
        echo PHP_EOL;

        foreach ($this->groupedCommands() as $group => $commands) {
            echo "  {$yellow}{$group}:{$r}" . PHP_EOL;

            foreach ($commands as $signature => $class) {
                $cmd      = new $class([]);
                $synopsis = $cmd->getSynopsis();
                $desc     = $cmd->getDescription();

                $namePart = str_pad($signature, 16);
                $synPart  = $synopsis ? str_pad($synopsis, 8) : str_repeat(' ', 8);

                echo "    {$green}{$namePart}{$r}  {$cyan}{$synPart}{$r}  {$dim}{$desc}{$r}" . PHP_EOL;
            }

            echo PHP_EOL;
        }
    }

    protected function groupedCommands(): array
    {
        $groups = ['Make' => [], 'Database' => [], 'Cache' => []];

        foreach ($this->commands as $signature => $class) {
            $prefix = explode(':', $signature)[0];
            $group  = match ($prefix) {
                'make'  => 'Make',
                'db'    => 'Database',
                'cache' => 'Cache',
                default => 'Other',
            };
            $groups[$group][$signature] = $class;
        }

        return array_filter($groups);
    }

    protected function version(): string
    {
        return defined('THEME_VERSION') ? 'v' . THEME_VERSION : 'v1.0';
    }
}
