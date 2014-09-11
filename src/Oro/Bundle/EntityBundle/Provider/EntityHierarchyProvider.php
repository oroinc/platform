<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

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
                $parents   = [];
                $this->loadParents($parents, $className);
                if (empty($parents)) {
                    continue;
                }

                // remove proxies if they are in list of parents
                $parents = array_filter(
                    $parents,
                    function ($parentClassName) {
                        return strpos($parentClassName, ExtendConfigDumper::ENTITY) !== 0;
                    }
                );
                if (empty($parents)) {
                    continue;
                }

                $this->hierarchy[$className] = $parents;
            }
        }
    }

    /**
     * Finds parent doctrine entities for given entity class name
     *
     * @param array  $result
     * @param string $className
     */
    protected function loadParents(array &$result, $className)
    {
        $reflection  = new \ReflectionClass($className);
        $parentClass = $reflection->getParentClass();
        if ($parentClass) {
            $parentClassName = $parentClass->getName();
            $em              = $this->doctrine->getManagerForClass($className);
            if (!$em->getMetadataFactory()->isTransient($parentClassName)) {
                $result[] = $parentClassName;
            }
            $this->loadParents($result, $parentClassName, $em);
        }
    }
}
