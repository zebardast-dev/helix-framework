<?php

namespace Helix\Console\Commands;

use Helix\Console\Command;

class CacheClear extends Command
{
    protected string $signature   = 'cache:clear';
    protected string $description = 'Clear all template cache';

    public function handle(): int
    {
        global $wpdb;

        $themeDir  = defined('THEME_DIR') ? THEME_DIR : getcwd();
        $cacheDir  = $themeDir . '/storage/cache';
        $this->newLine();

        // 1. Blade compiled files
        $viewCacheDir  = $cacheDir . '/views';
        $compiledCount = 0;

        if (is_dir($viewCacheDir)) {
            foreach (glob($viewCacheDir . '/*.php') ?: [] as $file) {
                unlink($file);
                $compiledCount++;
            }
        }

        $this->compiledCount($compiledCount);

        // 2. Discovery caches (components + composers)
        foreach (['components.php', 'composers.php'] as $file) {
            $path = $cacheDir . '/' . $file;
            if (file_exists($path)) {
                unlink($path);
                $this->success("Cleared {$file}.");
            } else {
                $this->comment("{$file} not found.");
            }
        }

        // 3. Output cache (transients)
        $results = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $wpdb->esc_like('helix_tpl_') . '%'
            )
        );

        $transientCount = 0;

        foreach ($results as $optionName) {
            $key = str_replace('_transient_', '', $optionName);
            delete_transient($key);
            wp_cache_delete($key, 'helix');
            $transientCount++;
        }

        if ($transientCount > 0) {
            $this->success("Cleared {$transientCount} output cache transient(s).");
        } else {
            $this->comment('No output cache transients found.');
        }

        $this->newLine();

        return 0;
    }

    private function compiledCount(int $count): void
    {
        if ($count > 0) {
            $this->success("Cleared {$count} compiled blade file(s).");
        } else {
            $this->comment('No compiled blade files found.');
        }
    }
}
