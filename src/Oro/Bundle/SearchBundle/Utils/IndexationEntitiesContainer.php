<?php

namespace Oro\Bundle\SearchBundle\Utils;

use Doctrine\Common\Util\ClassUtils;

/**
 * Container for entities which will be triggered to re-indexation.
 */
class IndexationEntitiesContainer
{
    /** @var array */
    protected $entities = [];

    /**
     * @param object $entity
     */
    public function addEntity($entity): void
    {
        $className = ClassUtils::getClass($entity);

        if (!isset($this->entities[$className])) {
            $this->entities[$className] = [];
        }

        $hash = spl_object_hash($entity);

        if (!array_key_exists($hash, $this->entities[$className])) {
            $this->entities[$className][$hash] = $entity;
        }
    }

    /**
     * @return array
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * @param string $className
     */
    public function removeEntities(string $className): void
    {
        unset($this->entities[$className]);
    }

    public function clear(): void
    {
        $this->entities = [];
    }
}
