<?php

namespace Helix\Models;

class Page extends BasePost
{
    public function slug(): string
    {
        return $this->post->post_name;
    }

    public function parent(): ?self
    {
        if (!$this->post->post_parent) {
            return null;
        }

        $parent = get_post($this->post->post_parent);
        return $parent ? new self($parent) : null;
    }

    public function hasParent(): bool
    {
        return $this->post->post_parent !== 0;
    }

    public function children(): array
    {
        $children = get_children([
            'post_parent' => $this->post->ID,
            'post_type'   => 'page',
            'post_status' => 'publish',
        ]);

        return array_map(fn($p) => new self($p), $children);
    }

    public function hasChildren(): bool
    {
        return !empty(get_pages(['parent' => $this->post->ID, 'post_type' => 'page']));
    }

    public function template(): string
    {
        return get_page_template_slug($this->post->ID);
    }

    public function order(): int
    {
        return (int) $this->post->menu_order;
    }

    public function ancestors(): array
    {
        return array_values(array_filter(
            array_map(function ($id) {
                $post = get_post($id);
                return $post ? new self($post) : null;
            }, array_reverse(get_post_ancestors($this->post->ID)))
        ));
    }
}
