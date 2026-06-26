<?php

namespace Helix\Console\Commands;

use Helix\Console\Command;

class MakeModel extends Command
{
    protected string $signature   = 'make:model';
    protected string $description = 'Create a new post model';
    protected string $synopsis    = '<Name>';

    public function handle(): int
    {
        $name = $this->arg(0);

        if (!$name) {
            $this->error('Name argument is required.');
            $this->comment('Usage: php helix make:model <Name>');
            return 1;
        }

        $themeDir  = defined('THEME_DIR') ? THEME_DIR : getcwd();
        $className = ucfirst($name);
        $path      = "{$themeDir}/app/Models/{$className}.php";

        $this->ensureDir(dirname($path));

        $content = <<<PHP
        <?php

        namespace App\Models;

        use Helix\Models\BasePost;

        class {$className} extends BasePost
        {
            //
        }
        PHP;

        if ($this->writeFile($path, $content)) {
            $this->success("Model {$className} created.");
            $this->comment("app/Models/{$className}.php");
        }

        return 0;
    }
}
