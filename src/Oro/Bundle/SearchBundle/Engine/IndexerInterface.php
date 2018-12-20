<?php

namespace Oro\Bundle\SearchBundle\Engine;

/**
 * Performs indexation operations for search index
 */
interface IndexerInterface
{
    const WEIGHT_FIELD = 'relevance_weight';

    /**
     * Save one of several entities to search index
     *
     * @param object|object[] $entity
     * @param array        $context
     *
     * @return bool
     */
    public function save($entity, array $context = []);

    /**
     * Delete one or several entities from search index
     *
     * @param object|object[] $entity
     * @param array $context
     *
     * @return bool
     */
    public function delete($entity, array $context = []);

    /**
     * Returns classes required to reindex for one or several classes
     * Returns all indexed classes if $class is null
     * @param string|string[]|null $class
     * @param array $context
     * @return string[]
     */
    public function getClassesForReindex($class = null, array $context = []);

    /**
     * Resets data for one or several classes in index
     * Resets data for all indexed classes if $class is null
     *
     * @param string|string[]|null $class
     * @param array $context
     */
    public function resetIndex($class = null, array $context = []);

    /**
     * Reindex data for one or several classes in index
     * Reindex data for all indexed classes if $class is null
     *
     * @param string|string[]|null $class
     * @param array           $context
     *
     * @return int Number of reindexed entities or null if information not available
     * @throws \LogicException
     */
    public function reindex($class = null, array $context = []);
}
