<?php

namespace Helix\Console\Commands;

use Helix\Console\Command;

class MakeSeeder extends Command
{
    protected string $signature   = 'make:seeder';
    protected string $description = 'Create a new database seeder';
    protected string $synopsis    = '<Name>';

    public function handle(): int
    {
        $name = $this->arg(0);

        if (!$name) {
            $this->error('Name argument is required.');
            $this->comment('Usage: php helix make:seeder <Name>');
            return 1;
        }

        $themeDir  = defined('THEME_DIR') ? THEME_DIR : getcwd();
        $className = ucfirst($name);
        if (!str_ends_with($className, 'Seeder')) {
            $className .= 'Seeder';
        }

        $path = "{$themeDir}/app/Database/Seeders/{$className}.php";

        $this->ensureDir(dirname($path));

        $content = <<<PHP
        <?php

        namespace App\Database\Seeders;

        use Helix\Database\Seeder;

        class {$className} extends Seeder
        {
            public function run(): void
            {
                \$fake = \$this->fake();

                foreach (range(1, 5) as \$i) {
                    \$id = \$this->post([
                        'post_title'   => \$fake->realText(50, 1),
                        'post_content' => \$fake->realText(600),
                        'post_excerpt' => \$fake->realText(120, 1),
                        'post_type'    => 'post',
                    ]);

                    // if (\$id) {
                    //     \$this->meta(\$id, '_my_field', \$fake->realText(50, 1));
                    // }
                }
            }
        }
        PHP;

        if ($this->writeFile($path, $content)) {
            $this->success("{$className} created.");
            $this->comment("app/Database/Seeders/{$className}.php");
            $this->newLine();
            $this->warn("Run it with: php helix db:seed {$className}");
        }

        return 0;
    }
}
