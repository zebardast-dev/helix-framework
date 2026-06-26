<?php

namespace Helix\Repositories;

use Helix\Query\QueryBuilder;
use Helix\Query\PostCollection;

abstract class BaseRepository
{
    protected string $model;
    protected string $postType = 'post';

    public function find(int $id): mixed
    {
        return $this->query()->id($id)->first($this->model);
    }

    public function findBySlug(string $slug): mixed
    {
        return $this->query()->slug($slug)->first($this->model);
    }

    public function all(): PostCollection
    {
        return $this->query()->limit(-1)->get($this->model);
    }

    public function latest(int $count = 10): PostCollection
    {
        return $this->query()->limit($count)->orderBy('date', 'DESC')->get($this->model);
    }

    public function paginate(int $perPage): PostCollection
    {
        return $this->query()->paginate($perPage, $this->model);
    }

    protected function query(): QueryBuilder
    {
        return (new QueryBuilder())->postType($this->postType);
    }
}
