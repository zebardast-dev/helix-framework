<?php

namespace Helix\Query;

use WP_Query;

class QueryBuilder
{
    protected array  $args          = [];
    protected array  $meta          = [];
    protected array  $tax           = [];
    protected string $metaRelation  = 'AND';
    protected string $taxRelation   = 'AND';

    protected array $allowedOrder = [
        'date', 'title', 'menu_order', 'meta_value', 'meta_value_num', 'rand',
    ];

    public function __construct()
    {
        $this->args = ['post_status' => 'publish'];
    }

    public function postType(string $type): self
    {
        $this->args['post_type'] = $type;
        return $this;
    }

    public function id(int $id): self
    {
        $this->args['p'] = absint($id);
        return $this;
    }

    public function slug(string $slug): self
    {
        $this->args['name'] = sanitize_title($slug);
        return $this;
    }

    public function limit(int $count): self
    {
        $this->args['posts_per_page'] = max(1, $count);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->args['offset'] = max(0, $offset);
        unset($this->args['paged']);
        return $this;
    }

    public function page(int $page): self
    {
        $this->args['paged'] = max(1, $page);
        return $this;
    }

    public function orderBy(string $field, string $direction = 'DESC'): self
    {
        if (!in_array($field, $this->allowedOrder)) $field = 'date';

        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) $direction = 'DESC';

        $this->args['orderby'] = $field;
        $this->args['order']   = $direction;

        return $this;
    }

    public function meta(string $key, mixed $value, string $compare = '='): self
    {
        $this->meta[] = ['key' => $key, 'value' => $value, 'compare' => $compare];
        return $this;
    }

    public function metaRelation(string $relation): self
    {
        $this->metaRelation = in_array(strtoupper($relation), ['AND', 'OR'])
            ? strtoupper($relation) : 'AND';
        return $this;
    }

    public function taxonomy(string $taxonomy, mixed $terms, string $field = 'term_id', string $operator = 'IN'): self
    {
        $this->tax[] = [
            'taxonomy' => $taxonomy,
            'field'    => in_array($field, ['term_id', 'slug', 'name']) ? $field : 'term_id',
            'terms'    => (array) $terms,
            'operator' => $operator,
        ];
        return $this;
    }

    public function taxRelation(string $relation): self
    {
        $this->taxRelation = in_array(strtoupper($relation), ['AND', 'OR'])
            ? strtoupper($relation) : 'AND';
        return $this;
    }

    public function query(): WP_Query
    {
        return new WP_Query($this->build());
    }

    public function get(string $model): PostCollection
    {
        return new PostCollection($this->query()->posts, $model);
    }

    public function paginate(int $perPage, string $model): PostCollection
    {
        $this->limit($perPage)->page($this->smartPage());
        $query = $this->query();
        return new PostCollection($query->posts, $model, $query);
    }

    public function first(string $model): mixed
    {
        return $this->limit(1)->get($model)->first();
    }

    protected function build(): array
    {
        if (!empty($this->meta)) {
            $this->args['meta_query'] = array_merge(
                ['relation' => $this->metaRelation],
                $this->meta
            );
        }

        if (!empty($this->tax)) {
            $this->args['tax_query'] = array_merge(
                ['relation' => $this->taxRelation],
                $this->tax
            );
        }

        return $this->args;
    }

    protected function smartPage(): int
    {
        $page = get_query_var('paged') ?: get_query_var('page');
        return $page ? (int) $page : 1;
    }
}
