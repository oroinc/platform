<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

interface EngineV2Interface
{
    /**
     * Performs search in index according to passed query
     *
     * @param Query $query
     * @return Result
     */
    public function search(Query $query);
}
