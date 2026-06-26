<?php

namespace Helix\Console\Commands;

use Helix\Console\Command;

class MakeRepository extends Command
{
    protected string $signature   = 'make:repository';
    protected string $description = 'Create a new repository';
    protected string $synopsis    = '<Name>';

    public function handle(): int
    {
        $name = $this->arg(0);

        if (!$name) {
            $this->error('Name argument is required.');
            $this->comment('Usage: php helix make:repository <Name>');
            return 1;
        }

        $themeDir   = defined('THEME_DIR') ? THEME_DIR : getcwd();
        $modelName  = ucfirst($name);
        $className  = $modelName . 'Repository';
        $postType   = $this->toSnake($modelName);
        $path       = "{$themeDir}/app/Repositories/{$className}.php";

        $this->ensureDir(dirname($path));

        $content = <<<PHP
        <?php

        namespace App\Repositories;

        use App\Models\\{$modelName};
        use Helix\Repositories\BaseRepository;

        class {$className} extends BaseRepository
        {
            protected string \$model    = {$modelName}::class;
            protected string \$postType = '{$postType}';
        }
        PHP;

        if ($this->writeFile($path, $content)) {
            $this->success("Repository {$className} created.");
            $this->comment("app/Repositories/{$className}.php");
        }

        return 0;
    }
}
