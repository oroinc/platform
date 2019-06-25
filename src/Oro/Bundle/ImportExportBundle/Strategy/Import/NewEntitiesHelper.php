<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

use Doctrine\Common\Util\ClassUtils;

/**
 * Storage for new entities
 */
class NewEntitiesHelper
{
    /**
     * Entities which can be reused to prevent duplicating,
     * accessible by unique key
     *
     * @var array
     */
    protected $newEntities = [];

    /**
     * Could be used for count usage of objects in some context
     *
     * @var array ['context_key + unique_object_key' => $usageCount]
     */
    protected $newEntitiesUsages = [];

    /**
     * @param string $key
     * @param null   $default
     *
     * @return object|null
     */
    public function getEntity($key, $default = null)
    {
        return $this->newEntities[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param object $value
     */
    public function setEntity($key, $value)
    {
        $this->newEntities[$key] = $value;
    }

    /**
     * @param string $hashKey
     */
    public function incrementEntityUsage($hashKey)
    {
        if (!isset($this->newEntitiesUsages[$hashKey])) {
            $this->newEntitiesUsages[$hashKey] = 0;
        }
        $this->newEntitiesUsages[$hashKey]++;
    }

    /**
     * @param string $hashKey
     *
     * @return int
     */
    public function getEntityUsage($hashKey)
    {
        return $this->newEntitiesUsages[$hashKey] ?? 0;
    }

    public function onFlush()
    {
        $this->newEntities       = [];
        $this->newEntitiesUsages = [];
    }

    public function onClear()
    {
        $this->newEntities       = [];
        $this->newEntitiesUsages = [];
    }

    /**
     * @param object $entity
     * @param null|string $prefix
     *
     * @return string
     */
    public function getEntityHashKey($entity, $prefix = null)
    {
        return $prefix . spl_object_hash($entity);
    }

    /**
     * Save new entity to newEntitiesHelper storage by key constructed from identityValues
     * and this strategy context for reuse if there will be entity with the same identity values
     * it has not been created again but has been fetched from this storage
     *
     * @param object $entity
     * @param array|null $identityValues
     * @param null|string $hashPrefix
     * @return object|null
     */
    public function storeNewEntity($entity, array $identityValues = null, $hashPrefix = null)
    {
        $knownNewEntity = null;
        if ($identityValues) {
            $entityClass = ClassUtils::getClass($entity);
            $newEntityKey = sprintf('%s:%s', $entityClass, serialize($identityValues));
            $knownNewEntity = $this->getEntity($newEntityKey);
            if (null === $knownNewEntity) {
                $this->setEntity($newEntityKey, $entity);
                $this->incrementEntityUsage($this->getEntityHashKey($entity, $hashPrefix));
            } else {
                $this->incrementEntityUsage($this->getEntityHashKey($knownNewEntity, $hashPrefix));
            }
        }

        return $knownNewEntity;
    }
}
