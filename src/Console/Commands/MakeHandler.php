<?php

namespace Helix\Console\Commands;

use Helix\Console\Command;

class MakeHandler extends Command
{
    protected string $signature   = 'make:handler';
    protected string $description = 'Create a new file upload handler';
    protected string $synopsis    = '<Name> [mime]';

    public function handle(): int
    {
        $name = $this->arg(0);

        if (!$name) {
            $this->error('Name argument is required.');
            $this->comment('Usage: php helix make:handler <Name> [mime/type]');
            return 1;
        }

        $themeDir  = defined('THEME_DIR') ? THEME_DIR : getcwd();
        $className = ucfirst($name) . 'Handler';
        $ext       = strtolower($name);
        $mimeType  = $this->arg(1) ?? "type/{$ext}";
        $path      = "{$themeDir}/app/Media/Handlers/{$className}.php";

        $this->ensureDir(dirname($path));

        $content = <<<PHP
        <?php

        namespace App\Media\Handlers;

        use Helix\Media\FileHandler;

        class {$className} extends FileHandler
        {
            public function mimes(): array
            {
                return ['{$ext}' => '{$mimeType}'];
            }

            // public function sanitize(array \$file): array
            // {
            //     if (!\$this->hasExtension(\$file['name'], '{$ext}')) {
            //         return \$file;
            //     }
            //     // Sanitize file content here
            //     return \$file;
            // }

            // public function adminHead(): void
            // {
            //     // Output CSS for media library preview
            // }
        }
        PHP;

        if ($this->writeFile($path, $content)) {
            $this->success("{$className} created.");
            $this->comment("app/Media/Handlers/{$className}.php");
            $this->newLine();
            $this->warn("Register it in functions.php:");
            $this->line("  ->allow(new App\\Media\\Handlers\\{$className}())");
        }

        return 0;
    }
}
