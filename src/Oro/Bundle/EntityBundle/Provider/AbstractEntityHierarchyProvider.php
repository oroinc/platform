<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The base class for providers that get parent entities/mapped superclasses for any configurable entity.
 */
abstract class AbstractEntityHierarchyProvider implements EntityHierarchyProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array */
    protected $hierarchy;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getHierarchy()
    {
        $this->ensureHierarchyInitialized();

        return $this->hierarchy;
    }

    /**
     * {@inheritdoc}
     */
    public function getHierarchyForClassName($className)
    {
        $this->ensureHierarchyInitialized();

        return $this->hierarchy[$className] ?? [];
    }

    /**
     * Loads the class hierarchy
     */
    abstract protected function initializeHierarchy();

    /**
     * Makes sure the class hierarchy was loaded
     */
    protected function ensureHierarchyInitialized()
    {
        if (null === $this->hierarchy) {
            $this->hierarchy = [];
            $this->initializeHierarchy();
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
            if (!str_starts_with($parentClassName, ExtendHelper::ENTITY_NAMESPACE)) {
                $em = $this->doctrineHelper->getEntityManagerForClass($className, false);
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
