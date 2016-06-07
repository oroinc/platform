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
     * Returns required classes for reindex by class.
     *
     * @param string|string[] $class
     *
     * @return string[]
     */
    public function getClassesForReindex($class = null);

    /**
     * Resets one or several indexes. Resets all indexes if class is null.
     *
     * @param string|string[] $class
     */
    public function resetIndex($class = null);

    /**
     * Reindex one or several indexes. Reindex all indexes if class is null.
     *
     * @param string|string[] $class
     */
    public function reindex($class = null);
}
