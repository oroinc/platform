<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * This class allows to get parent entities/mapped superclasses for any configurable entity
 */
class EntityHierarchyProvider
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    protected $hierarchy;

    /**
     * @param ConfigProvider  $extendConfigProvider
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ConfigProvider $extendConfigProvider, ManagerRegistry $doctrine)
    {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->doctrine             = $doctrine;
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

            $entityConfigs = $this->extendConfigProvider->getConfigs();
            foreach ($entityConfigs as $entityConfig) {
                if ($entityConfig->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_DELETE])) {
                    continue;
                }
                if ($entityConfig->is('is_deleted')) {
                    continue;
                }

                $className = $entityConfig->getId()->getClassName();
                $parents   = $this->loadParents($className);
                if (!empty($parents)) {
                    $this->hierarchy[$className] = $parents;
                }
            }
        }
    }

    /**
     * Finds parent doctrine entities for given entity class name
     *
     * @param string $className
     *
     * @return string[]
     */
    protected function loadParents($className)
    {
        $result = [];

        $reflection  = new \ReflectionClass($className);
        $parentClass = $reflection->getParentClass();
        while ($parentClass) {
            $parentClassName = $parentClass->getName();
            // a parent class should be:
            // - not extended entity proxy
            // - registered in Doctrine, for example Entity or MappedSuperclass
            if (strpos($parentClassName, ExtendHelper::ENTITY_NAMESPACE) !== 0) {
                $em = $this->doctrine->getManagerForClass($className);
                if ($em && !$em->getMetadataFactory()->isTransient($parentClassName)) {
                    $result[] = $parentClassName;
                }
            }

            $reflection  = new \ReflectionClass($parentClassName);
            $parentClass = $reflection->getParentClass();
        }

        return $result;
    }
}
