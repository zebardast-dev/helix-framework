<?php

namespace Helix\View;

use Throwable;

class Renderer
{
    public function render(string $view, array $data = []): string
    {
        if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return '';
        }

        $output = '';
        $post   = get_post();

        try {
            if ($post) setup_postdata($post);

            $data = array_merge([
                'post'     => $post,
                'wp_query' => $GLOBALS['wp_query'] ?? null,
            ], $data);

            $data   = ComposerManager::run($view, $data);
            $output = view($view, $data)->render();

        } catch (Throwable $e) {
            error_log('[Helix Renderer] ' . $e->getMessage());
            return defined('WP_DEBUG') && WP_DEBUG ? 'Error: ' . $e->getMessage() : '';
        } finally {
            if ($post) wp_reset_postdata();
        }

        return $output;
    }
}
