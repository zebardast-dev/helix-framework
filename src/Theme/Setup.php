<?php

namespace Helix\Theme;

if (!defined('ABSPATH')) exit;

class Setup
{
    public function __construct()
    {
        add_action('after_setup_theme', [$this, 'setup']);
    }

    public function setup(): void
    {
        load_theme_textdomain('helix', get_template_directory() . '/languages');

        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('automatic-feed-links');
        add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
        add_theme_support('custom-logo', [
            'height'      => 100,
            'width'       => 300,
            'flex-height' => true,
            'flex-width'  => true,
        ]);
        add_theme_support('editor-styles');
        add_theme_support('woocommerce');

        add_editor_style('assets/css/editor.css');

        add_image_size('thumb-sm', 150, 150, true);
        add_image_size('thumb-md', 400, 300, true);
        add_image_size('thumb-lg', 800, 600, true);

        register_nav_menus([
            'primary'    => __('Primary Menu',    'helix'),
            'categories' => __('Categories Menu', 'helix'),
            'side'       => __('Side Menu',       'helix'),
        ]);
    }
}
