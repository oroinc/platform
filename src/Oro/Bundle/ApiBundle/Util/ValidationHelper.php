<?php

namespace Oro\Bundle\ApiBundle\Util;

use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;

class ValidationHelper
{
    /** @var MetadataFactoryInterface */
    protected $metadataFactory;

    /**
     * @param MetadataFactoryInterface $metadataFactory
     */
    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Gets the validation metadata for a given class.
     *
     * @param string $className
     *
     * @return ClassMetadataInterface|null
     */
    public function getValidationMetadataForClass($className)
    {
        return $this->metadataFactory->hasMetadataFor($className)
            ? $this->metadataFactory->getMetadataFor($className)
            : null;
    }

    /**
     * Gets the validation metadata for a given property.
     *
     * @param string $className
     * @param string $propertyName
     *
     * @return PropertyMetadataInterface[]
     */
    public function getValidationMetadataForProperty($className, $propertyName)
    {
        $classMetadata = $this->getValidationMetadataForClass($className);
        if (null === $classMetadata) {
            return [];
        }

        return $classMetadata->getPropertyMetadata($propertyName);
    }

    /**
     * Checks whether a class has a given validation constraint.
     *
     * @param string $className
     * @param string $constraintClass
     * @param string $group
     *
     * @return bool
     */
    public function hasValidationConstraintForClass($className, $constraintClass, $group = 'Default')
    {
        $metadata = $this->getValidationMetadataForClass($className);
        if (null === $metadata) {
            return false;
        }

        return $this->hasValidationConstraint($metadata, $constraintClass, $group);
    }

    /**
     * Checks whether a property has a given validation constraint.
     *
     * @param string $className
     * @param string $propertyName
     * @param string $constraintClass
     * @param string $group
     *
     * @return bool
     */
    public function hasValidationConstraintForProperty($className, $propertyName, $constraintClass, $group = 'Default')
    {
        $metadatas = $this->getValidationMetadataForProperty($className, $propertyName);
        if (empty($metadatas)) {
            return false;
        }

        foreach ($metadatas as $metadata) {
            if ($this->hasValidationConstraint($metadata, $constraintClass, $group)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether a given validation metadata contains a given validation constraint.
     *
     * @param MetadataInterface $metadata
     * @param string            $constraintClass
     * @param string            $group
     *
     * @return bool
     */
    public function hasValidationConstraint(MetadataInterface $metadata, $constraintClass, $group)
    {
        $constraints = $metadata->findConstraints($group);
        foreach ($constraints as $constraint) {
            if (get_class($constraint) === $constraintClass) {
                return true;
            }
        }

        return false;
    }
}
