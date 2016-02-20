<?php

namespace Oro\Bundle\EntityBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;

use Doctrine\ORM\EntityManager;

class Registry extends DoctrineRegistry
{
    /** @var EntityManager[] */
    private $cachedManagers = [];

    /** @var array */
    private $managersMap = [];

    /**
     * {@inheritdoc}
     */
    public function resetManager($name = null)
    {
        $this->cachedManagers = [];
        $this->managersMap = [];

        return parent::resetManager($name);
    }

    /**
     * @param string $entityClass The real class name of an entity
     *
     * @return EntityManager|null
     */
    public function getManagerForClass($entityClass)
    {
        if (!array_key_exists($entityClass, $this->managersMap)) {
            $manager = parent::getManagerForClass($entityClass);
            if (null !== $manager) {
                $hash = spl_object_hash($manager);
                $this->cachedManagers[$hash] = $manager;
                $this->managersMap[$entityClass] = $hash;
            } else {
                $this->managersMap[$entityClass] = null;
            }

            return $manager;
        }

        return $this->managersMap[$entityClass] ? $this->cachedManagers[$this->managersMap[$entityClass]] : null;
    }
}
