<?php

namespace Helix\Template;

class Loader
{
    protected bool $booted = false;

    public function boot(): void
    {
        if ($this->booted) return;
        $this->booted = true;

        add_filter('template_include', [$this, 'load'], 100);
    }

    public function load(string $template): string
    {
        if (get_query_var('blade_view')) {
            return $this->templateFile();
        }

        $view = app(Hierarchy::class)->resolve();

        if ($view) {
            set_query_var('blade_view', $view);
            return $this->templateFile();
        }

        return $template;
    }

    protected function templateFile(): string
    {
        if (app()->has('helix.template_file')) {
            return app('helix.template_file');
        }

        return get_template_directory() . '/bootstrap/view.php';
    }
}
