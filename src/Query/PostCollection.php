<?php

namespace Helix\Query;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use WP_Query;

class PostCollection implements IteratorAggregate, Countable
{
    protected array     $items = [];
    protected string    $model;
    protected ?WP_Query $query = null;

    public function __construct(array $posts, string $model, ?WP_Query $query = null)
    {
        $this->model = $model;
        $this->query = $query;

        foreach ($posts as $post) {
            $this->items[] = new $model($post);
        }
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function pages(): int
    {
        return $this->query ? (int) $this->query->max_num_pages : 1;
    }

    public function currentPage(): int
    {
        $page = get_query_var('paged') ?: get_query_var('page');
        return $page ? (int) $page : 1;
    }

    public function query(): ?WP_Query
    {
        return $this->query;
    }
}
