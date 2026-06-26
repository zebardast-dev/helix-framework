<?php

namespace Helix\Console\Commands;

use Helix\Console\Command;

class MakeCommand extends Command
{
    protected string $signature   = 'make:command';
    protected string $description = 'Create a new CLI command';
    protected string $synopsis    = '<Name>';

    public function handle(): int
    {
        $name = $this->arg(0);

        if (!$name) {
            $this->error('Name argument is required.');
            $this->comment('Usage: php helix make:command <Name>');
            return 1;
        }

        $themeDir  = defined('THEME_DIR') ? THEME_DIR : getcwd();
        $className = ucfirst($name);
        $signature = $this->toKebab($className);
        $path      = "{$themeDir}/app/Console/Commands/{$className}.php";

        $this->ensureDir(dirname($path));

        $content = <<<PHP
        <?php

        namespace App\Console\Commands;

        use Helix\Console\Command;

        class {$className} extends Command
        {
            protected string \$signature   = '{$signature}';
            protected string \$description = '';

            public function handle(): int
            {
                return 0;
            }
        }
        PHP;

        if ($this->writeFile($path, $content)) {
            $this->success("Command {$className} created.");
            $this->comment("app/Console/Commands/{$className}.php");
            $this->newLine();
            $this->warn("Register it in core/commands.php to activate.");
        }

        return 0;
    }
}
