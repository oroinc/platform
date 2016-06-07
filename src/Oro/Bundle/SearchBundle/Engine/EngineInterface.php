<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

interface EngineInterface
{
    /**
     * Search query with query builder
     *
     * @param Query $query
     * @return Result
     */
    public function search(Query $query);
}
