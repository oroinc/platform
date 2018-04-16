<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * This class is responsible for creating an instance of an entity or a model inherited from an entity
 * via constructor or if it is not possible via reflection.
 */
class EntityInstantiator
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Creates an instance of a given class.
     *
     * @param string $className
     *
     * @return object
     */
    public function instantiate(string $className)
    {
        $reflClass = new \ReflectionClass($className);

        return $this->isInstantiableViaConstructor($reflClass)
            ? $this->instantiateViaConstructor($reflClass)
            : $this->instantiateViaReflection($reflClass);
    }

    /**
     * @param \ReflectionClass $reflClass
     *
     * @return bool
     */
    private function isInstantiableViaConstructor(\ReflectionClass $reflClass): bool
    {
        $constructor = $reflClass->getConstructor();

        return
            null === $constructor
            || (
                $constructor->isPublic()
                && 0 === $constructor->getNumberOfRequiredParameters()
            );
    }

    /**
     * @param \ReflectionClass $reflClass
     *
     * @return object
     */
    private function instantiateViaConstructor(\ReflectionClass $reflClass)
    {
        return $reflClass->newInstance();
    }

    /**
     * @param \ReflectionClass $reflClass
     *
     * @return object
     */
    private function instantiateViaReflection(\ReflectionClass $reflClass)
    {
        $entity = $reflClass->newInstanceWithoutConstructor();

        $metadata = $this->getEntityMetadata($reflClass);
        if (null !== $metadata) {
            $associations = $metadata->getAssociationNames();
            foreach ($associations as $propertyName) {
                if (!$metadata->isCollectionValuedAssociation($propertyName)) {
                    continue;
                }
                $property = ReflectionUtil::getProperty($reflClass, $propertyName);
                if (null === $property) {
                    continue;
                }

                if (!$property->isPublic()) {
                    $property->setAccessible(true);
                }
                $property->setValue($entity, new ArrayCollection());
            }
        }

        return $entity;
    }

    /**
     * @param \ReflectionClass $reflClass
     *
     * @return ClassMetadata|null
     */
    private function getEntityMetadata(\ReflectionClass $reflClass): ?ClassMetadata
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($reflClass->getName(), false);
        if (null === $metadata) {
            $parentClass = $reflClass->getParentClass();
            while ($parentClass) {
                $metadata = $this->doctrineHelper->getEntityMetadataForClass($parentClass->getName(), false);
                if (null !== $metadata) {
                    break;
                }
                $parentClass = $parentClass->getParentClass();
            }
        }

        return $metadata;
    }
}
