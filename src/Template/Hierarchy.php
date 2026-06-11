<?php

namespace Helix\Template;

class Hierarchy
{
    protected Cache $cache;

    public function __construct()
    {
        $this->cache = new Cache();
    }

    public function resolve(): string
    {
        $key = $this->cacheKey();

        if ($cached = $this->cache->get($key)) {
            return $cached;
        }

        $templates = apply_filters('helix/template/hierarchy', $this->templates());

        foreach ($templates as $tpl) {
            $view = str_replace('/', '.', $tpl);

            if (view()->exists($view)) {
                $this->cache->put($key, $view);
                return trim($view);
            }
        }

        $this->cache->put($key, 'index');
        return 'index';
    }

    protected function templates(): array
    {
        $templates = [];

        if (is_embed()) return ['embed'];

        if (is_404())          $templates[] = '404';
        if (is_search())       $templates[] = 'search';
        if (is_front_page())   $templates[] = 'front-page';
        if (is_home())         $templates[] = 'home';
        if (is_privacy_policy()) $templates[] = 'privacy-policy';

        if (is_page()) {
            $post = get_queried_object();

            $slug = get_page_template_slug();
            if ($slug) {
                $templates[] = str_replace(['.blade.php', '.php'], '', $slug);
            }

            $templates[] = "page-{$post->post_name}";
            $templates[] = "page/{$post->post_name}";
            $templates[] = "page-{$post->ID}";
            $templates[] = "page/{$post->ID}";
            $templates[] = 'page';
        }

        if (is_singular()) {
            $post = get_queried_object();

            $slug = get_page_template_slug();
            if ($slug) {
                $templates[] = str_replace(['.blade.php', '.php'], '', $slug);
            }

            $templates[] = "single-{$post->post_type}-{$post->post_name}";
            $templates[] = "single-{$post->post_type}";
            $templates[] = "single/{$post->post_type}";
            $templates[] = 'single';
        }

        if (is_archive()) {

            if (is_post_type_archive()) {
                $type = get_query_var('post_type');
                if (is_array($type)) $type = reset($type);
                $templates[] = "archive-{$type}";
                $templates[] = "archive/{$type}";
            }

            if (is_category() || is_tag() || is_tax()) {
                $term        = get_queried_object();
                $templates[] = "taxonomy-{$term->taxonomy}-{$term->slug}";
                $templates[] = "taxonomy-{$term->taxonomy}";
                $templates[] = "archive/{$term->taxonomy}";
            }

            if (is_author()) {
                $author      = get_queried_object();
                $templates[] = "author-{$author->user_nicename}";
                $templates[] = "author";
            }

            $templates[] = 'archive';
        }

        if (is_category()) {
            $term        = get_queried_object();
            $templates[] = "category-{$term->slug}";
            $templates[] = 'category';
        }

        if (is_tag()) {
            $term        = get_queried_object();
            $templates[] = "tag-{$term->slug}";
            $templates[] = 'tag';
        }

        if (is_date())  $templates[] = 'date';
        if (is_paged()) $templates[] = 'paged';

        $templates[] = 'index';

        return array_unique($templates);
    }

    protected function cacheKey(): string
    {
        return md5(
            (is_front_page() ? 'front' : '') .
            (is_home() ? 'home' : '') .
            (is_single() ? get_post_type() : '') .
            (is_page() ? get_queried_object_id() : '') .
            ($_SERVER['REQUEST_URI'] ?? '')
        );
    }
}
