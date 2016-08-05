<?php

namespace Oro\Bundle\SearchBundle\Engine;

interface IndexerInterface
{
    /**
     * Save one of several entities to search index
     *
     * @param object|array $entity
     * @param array        $context
     *
     * @return bool
     */
    public function save($entity, $context = []);

    /**
     * Delete one or several entities from search index
     *
     * @param object|array $entity
     * @param array        $context
     *
     * @return bool
     */
    public function delete($entity, $context = []);

    /**
     * Returns classes required to reindex for one or several classes
     * Returns all indexed classes if $class is null
     *
     * @param string|string[] $class
     * @param array           $context
     *
     * @return string[]
     */
    public function getClassesForReindex($class = null, $context = []);

    /**
     * Resets data for one or several classes in index
     * Resets data for all indexed classes if $class is null
     *
     * @param string|string[] $class
     * @param array           $context
     */
    public function resetIndex($class = null, $context = []);

    /**
     * Reindex data for one or several classes in index
     * Reindex data for all indexed classes if $class is null
     *
     * @param string|string[] $class
     * @param array           $context
     */
    public function reindex($class = null, $context = []);
}
