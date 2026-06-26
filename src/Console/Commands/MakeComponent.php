<?php

namespace Helix\Console\Commands;

use Helix\Console\Command;

class MakeComponent extends Command
{
    protected string $signature   = 'make:component';
    protected string $description = 'Create a new view component';
    protected string $synopsis    = '<Name>';

    public function handle(): int
    {
        $name = $this->arg(0);

        if (!$name) {
            $this->error('Name argument is required.');
            $this->comment('Usage: php helix make:component <Name>');
            return 1;
        }

        $themeDir  = defined('THEME_DIR') ? THEME_DIR : getcwd();
        $className = ucfirst($name);
        $kebab     = $this->toKebab($className);

        $phpPath   = "{$themeDir}/app/View/Components/{$className}.php";
        $viewDir   = "{$themeDir}/resources/views/components/{$kebab}";
        $bladePath = "{$viewDir}/{$kebab}.blade.php";
        $scssPath  = "{$viewDir}/{$kebab}.scss";
        $jsPath    = "{$viewDir}/{$kebab}.js";

        $this->ensureDir(dirname($phpPath));
        $this->ensureDir($viewDir);

        $php = <<<PHP
        <?php

        namespace App\View\Components;

        use Illuminate\View\Component;

        class {$className} extends Component
        {
            public function __construct()
            {
                //
            }

            public function render()
            {
                return repeatedView('components', '{$kebab}', 2);
            }
        }
        PHP;

        $blade = <<<BLADE
        <div class="{$kebab}">
            {{-- {$className} --}}
        </div>
        BLADE;

        $scss = ".{$kebab} {}\n";
        $js   = "// {$className} component\n";

        $ok = $this->writeFile($phpPath, $php)
           && $this->writeFile($bladePath, $blade)
           && $this->writeFile($scssPath, $scss)
           && $this->writeFile($jsPath, $js);

        if ($ok) {
            $this->success("Component {$className} created.");
            $this->comment("app/View/Components/{$className}.php");
            $this->comment("resources/views/components/{$kebab}/{$kebab}.blade.php");
            $this->comment("resources/views/components/{$kebab}/{$kebab}.scss");
            $this->comment("resources/views/components/{$kebab}/{$kebab}.js");
        }

        return $ok ? 0 : 1;
    }
}
