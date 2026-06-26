<?php

namespace Helix\Console\Commands;

use Helix\Console\Command;

class DbSeed extends Command
{
    protected string $signature   = 'db:seed';
    protected string $description = 'Run database seeders';
    protected string $synopsis    = '[Seeder]';

    public function handle(): int
    {
        $name = $this->arg(0) ?? 'DatabaseSeeder';

        if (!str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        $class = "App\\Database\\Seeders\\{$name}";

        if (!class_exists($class)) {
            $this->error("Seeder [{$name}] not found.");
            $this->comment("Create it with: php helix make:seeder {$name}");
            return 1;
        }

        $this->line("Running {$this->bold($name)}...");
        $this->newLine();

        (new $class())->run();

        $this->newLine();
        $this->success("Done.");

        return 0;
    }
}
