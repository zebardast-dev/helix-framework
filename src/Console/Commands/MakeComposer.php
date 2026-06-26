<?php

namespace Helix\Console\Commands;

use Helix\Console\Command;

class MakeComposer extends Command
{
    protected string $signature   = 'make:composer';
    protected string $description = 'Create a new view composer';
    protected string $synopsis    = '<Name>';

    public function handle(): int
    {
        $name = $this->arg(0);

        if (!$name) {
            $this->error('Name argument is required.');
            $this->comment('Usage: php helix make:composer <Name>');
            return 1;
        }

        $themeDir  = defined('THEME_DIR') ? THEME_DIR : getcwd();
        $className = ucfirst($name);

        if (!str_ends_with($className, 'Composer')) {
            $className .= 'Composer';
        }

        $path = "{$themeDir}/app/View/Composers/{$className}.php";

        $this->ensureDir(dirname($path));

        $content = <<<PHP
        <?php

        namespace App\View\Composers;

        class {$className}
        {
            public function __construct()
            {
                //
            }

            public function views(): array
            {
                return [''];
            }

            public function compose(string \$view, array \$data = []): array
            {
                return [];
            }
        }
        PHP;

        if ($this->writeFile($path, $content)) {
            $this->success("Composer {$className} created.");
            $this->comment("app/View/Composers/{$className}.php");
        }

        return 0;
    }
}
