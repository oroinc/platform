<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Bundle\DoctrineBundle\Registry as BaseRegistry;

class Registry extends BaseRegistry
{
    /** @var string[] [entity class => manager hash, ...] */
    private $managersMap = [];

    /** @var array [manager hash => manager|null, ...] */
    private $cachedManagers = [];

    /**
     * {@inheritdoc}
     */
    public function resetManager($name = null)
    {
        $this->managersMap    = [];
        $this->cachedManagers = [];

        parent::resetManager($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerForClass($class)
    {
        if (array_key_exists($class, $this->managersMap)) {
            return $this->cachedManagers[$this->managersMap[$class]];
        }

        $manager = parent::getManagerForClass($class);
        $hash    = null !== $manager ? spl_object_hash($manager) : '';

        $this->managersMap[$class]   = $hash;
        $this->cachedManagers[$hash] = $manager;

        return $manager;
    }
}
