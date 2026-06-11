<?php

namespace Helix\Models;

class Post extends BasePost
{
    public function categories(): array
    {
        return get_the_category($this->post->ID) ?: [];
    }

    public function tags(): array
    {
        return get_the_tags($this->post->ID) ?: [];
    }

    public function commentsCount(): int
    {
        return (int) $this->post->comment_count;
    }

    public function isSticky(): bool
    {
        return is_sticky($this->post->ID);
    }

    public function modifiedDate(string $format = 'Y-m-d'): string
    {
        return get_the_modified_date($format, $this->post);
    }
}
