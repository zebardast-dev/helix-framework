<?php

namespace Helix\Console\Commands;

use Helix\Console\Command;

class MakePostType extends Command
{
    protected string $signature   = 'make:post-type';
    protected string $description = 'Create a new post type class';
    protected string $synopsis    = '<Name>';

    public function handle(): int
    {
        $name = $this->arg(0);

        if (!$name) {
            $this->error('Name argument is required.');
            $this->comment('Usage: php helix make:post-type <Name>');
            return 1;
        }

        $themeDir  = defined('THEME_DIR') ? THEME_DIR : getcwd();
        $className = ucfirst($name) . 'PostType';
        $singular  = ucfirst($name);
        $plural    = $singular . 's';
        $key       = $this->toSnake($name);
        $path      = "{$themeDir}/app/PostTypes/{$className}.php";

        $this->ensureDir(dirname($path));

        $content = <<<PHP
        <?php

        namespace App\PostTypes;

        use Helix\PostType\PostType;

        class {$className} extends PostType
        {
            protected string \$key      = '{$key}';
            protected string \$singular = '{$singular}';
            protected string \$plural   = '{$plural}s';
            protected string \$icon     = 'dashicons-admin-post';
            protected array  \$supports = ['title', 'editor', 'thumbnail'];

            protected array \$taxonomies = [
                // '{$key}_category' => [
                //     'singular'     => '{$singular} Category',
                //     'plural'       => '{$singular} Categories',
                //     'hierarchical' => true,
                // ],
            ];

            // Override specific admin labels (useful for non-English sites)
            protected array \$labels = [
                // 'add_new_item'       => 'Add {$singular}',
                // 'edit_item'          => 'Edit {$singular}',
                // 'not_found'          => 'No {$plural}s found',
                // 'not_found_in_trash' => 'No {$plural}s found in Trash',
            ];

            public function boot(): void
            {
                // Register hooks and AJAX handlers here
                // Action::add('wp_ajax_{$key}_action', [\$this, 'handleAction']);
            }
        }
        PHP;

        if ($this->writeFile($path, $content)) {
            $this->success("Post type {$className} created.");
            $this->comment("app/PostTypes/{$className}.php");
            $this->newLine();
            $this->warn("Register it in config/app.php under 'post_types' to activate.");
        }

        return 0;
    }
}
