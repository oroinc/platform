<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * This class allows to get parent entities/mapped superclasses for any configurable entity
 */
class EntityHierarchyProvider
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var array */
    protected $hierarchy;

    /**
     * @param ConfigProvider $entityConfigProvider
     */
    public function __construct(ConfigProvider $entityConfigProvider)
    {
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * Gets the hierarchy for all entities who has at least one parent entity/mapped superclass
     *
     * @return array
     */
    public function getHierarchy()
    {
        $this->ensureHierarchyInitialized();

        return $this->hierarchy;
    }

    /**
     * Gets parent entities/mapped superclasses for the given entity
     *
     * @param string $className
     * @return array
     */
    public function getHierarchyForClassName($className)
    {
        $this->ensureHierarchyInitialized();

        return isset($this->hierarchy[$className])
            ? $this->hierarchy[$className]
            : [];
    }

    /**
     * Makes sure the class hierarchy was loaded
     */
    protected function ensureHierarchyInitialized()
    {
        if (null === $this->hierarchy) {
            $this->hierarchy = [];

            $em       = $this->entityConfigProvider->getConfigManager()->getEntityManager();
            $entities = $this->entityConfigProvider->getIds();
            foreach ($entities as $entity) {
                $className = $entity->getClassName();
                $parents   = [];
                $this->loadParents($parents, $className, $em);
                if ($parents) {
                    $this->hierarchy[$className] = $parents;
                }
            }
        }
    }

    /**
     * Finds parent doctrine entities for given entity class name
     *
     * @param array         $result
     * @param string        $className
     * @param EntityManager $em
     */
    protected function loadParents(array &$result, $className, EntityManager $em)
    {
        $reflection  = new \ReflectionClass($className);
        $parentClass = $reflection->getParentClass();
        if ($parentClass) {
            $parentClassName = $parentClass->getName();
            if (!$em->getMetadataFactory()->isTransient($parentClassName)) {
                $result[] = $parentClassName;
            }
            $this->loadParents($result, $parentClassName, $em);
        }
    }
}
