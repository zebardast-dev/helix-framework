<?php

namespace Helix\Repositories;

use Helix\Query\QueryBuilder;

abstract class BaseRepository
{
    protected function query(): QueryBuilder
    {
        return new QueryBuilder();
    }
}
