<?php

namespace Oro\Bundle\SearchBundle\Engine;

interface IndexerInterface
{
    /**
     * Save one of several entities to search index
     *
     * @param object|array $entity
     *
     * @return bool
     */
    public function save($entity);

    /**
     * Delete one or several entities from search index
     *
     * @param object|array $entity
     *
     * @return bool
     */
    public function delete($entity);

    /**
     * Returns classes required to reindex for one or several classes
     * Returns all indexed classes if $class is null
     *
     * @param string|string[] $class
     *
     * @return string[]
     */
    public function getClassesForReindex($class = null);

    /**
     * Resets data for one or several classes in index
     * Resets data for all indexed classes if $class is null
     *
     * @param string|string[] $class
     */
    public function resetIndex($class = null);

    /**
     * Reindex data for one or several classes in index
     * Reindex data for all indexed classes if $class is null
     *
     * @param string|string[] $class
     */
    public function reindex($class = null);
}
