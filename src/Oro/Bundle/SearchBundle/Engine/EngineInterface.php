<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

interface EngineInterface
{
    /**
     * Delete one or several entities from search index
     *
     * @param object|array $entity
     * @param bool $realTime True - do immediately, false - put to queue
     * @return bool
     */
    public function delete($entity, $realTime = true);

    /**
     * Reindex entity data
     *
     * @param string|null $class
     * @param int|null        $offset
     * @param int|null        $limit
     *
     * @return int Number of reindexed entities
     */
    public function reindex($class = null, $offset = null, $limit = null);

    /**
     * Save one of several entities to search index
     *
     * @param object|array $entity
     * @param bool $realTime True - do immediately, false - put to queue
     * @return bool
     */
    public function save($entity, $realTime = true);

    /**
     * Search query with query builder
     *
     * @param Query $query
     * @return Result
     */
    public function search(Query $query);
}
