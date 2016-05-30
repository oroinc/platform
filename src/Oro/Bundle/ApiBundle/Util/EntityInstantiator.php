<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\PhpUtils\ReflectionUtil;

class EntityInstantiator
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

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
    public function instantiate($className)
    {
        $reflClass = new \ReflectionClass($className);

        return $this->mustBeInstantiatedWithoutConstructor($reflClass)
            ? $this->instantiateViaReflection($reflClass)
            : $this->instantiateViaConstructor($reflClass);
    }

    /**
     * @param \ReflectionClass $reflClass
     *
     * @return bool
     */
    protected function mustBeInstantiatedWithoutConstructor(\ReflectionClass $reflClass)
    {
        $constructor = $reflClass->getConstructor();

        return
            null !== $constructor
            && (
                !$constructor->isPublic()
                || 0 !== $constructor->getNumberOfRequiredParameters()
            );
    }

    /**
     * @param \ReflectionClass $reflClass
     *
     * @return object
     */
    protected function instantiateViaConstructor(\ReflectionClass $reflClass)
    {
        return $reflClass->newInstance();
    }

    /**
     * @param \ReflectionClass $reflClass
     *
     * @return object
     */
    protected function instantiateViaReflection(\ReflectionClass $reflClass)
    {
        $entity = $reflClass->newInstanceWithoutConstructor();

        $metadata = $this->doctrineHelper->getEntityMetadataForClass($reflClass->getName(), false);
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
}
