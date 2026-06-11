<?php

namespace Helix\Models;

use WP_Post;
use Helix\Support\Image;

abstract class BasePost
{
    protected WP_Post $post;

    public function __construct(WP_Post $post)
    {
        $this->post = $post;
    }

    public static function make(int $id): ?static
    {
        $post = get_post($id);
        return $post ? new static($post) : null;
    }

    public static function from(WP_Post $post): static
    {
        return new static($post);
    }

    public function id(): int
    {
        return $this->post->ID;
    }

    public function title(): string
    {
        return get_the_title($this->post);
    }

    public function url(): string
    {
        return get_permalink($this->post);
    }

    public function excerpt(int $length = 55): string
    {
        $excerpt = get_the_excerpt($this->post);

        if (empty($excerpt)) {
            $excerpt = wp_strip_all_tags(get_the_content(null, false, $this->post));
        }

        $excerpt = wp_strip_all_tags($excerpt);

        if (mb_strlen($excerpt) <= $length) {
            return $excerpt;
        }

        return mb_substr($excerpt, 0, $length) . '…';
    }

    public function content(): string
    {
        return apply_filters('the_content', $this->post->post_content);
    }

    public function date(string $format = 'Y-m-d'): string
    {
        return get_the_date($format, $this->post);
    }

    public function author(): string
    {
        return get_the_author_meta('display_name', $this->post->post_author);
    }

    public function thumbnail(string $size = 'full'): ?Image
    {
        if (!has_post_thumbnail($this->id())) {
            return null;
        }

        return Image::fromAttachment(get_post_thumbnail_id($this->id()), $size);
    }

    public function meta(string $key, mixed $default = null): mixed
    {
        $value = get_post_meta($this->id(), $key, true);
        return $value !== '' ? $value : $default;
    }

    public function raw(): WP_Post
    {
        return $this->post;
    }
}
