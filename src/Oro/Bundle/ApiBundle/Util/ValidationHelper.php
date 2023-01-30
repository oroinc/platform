<?php

namespace Oro\Bundle\ApiBundle\Util;

use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;

/**
 * Provides a set og utility methods that help to do data validation.
 */
class ValidationHelper
{
    private MetadataFactoryInterface $metadataFactory;

    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Gets the validation metadata for a given class.
     */
    public function getValidationMetadataForClass(string $className): ?ClassMetadataInterface
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
    public function getValidationMetadataForProperty(string $className, string $propertyName): array
    {
        $classMetadata = $this->getValidationMetadataForClass($className);
        if (null === $classMetadata) {
            return [];
        }

        return $classMetadata->getPropertyMetadata($propertyName);
    }

    /**
     * Checks whether a class has a given validation constraint.
     */
    public function hasValidationConstraintForClass(
        string $className,
        string $constraintClass,
        string $group = 'Default'
    ): bool {
        $metadata = $this->getValidationMetadataForClass($className);
        if (null === $metadata) {
            return false;
        }

        return $this->hasValidationConstraint($metadata, $constraintClass, $group);
    }

    /**
     * Checks whether a property has a given validation constraint.
     */
    public function hasValidationConstraintForProperty(
        string $className,
        string $propertyName,
        string $constraintClass,
        string $group = 'Default'
    ): bool {
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
     */
    public function hasValidationConstraint(MetadataInterface $metadata, string $constraintClass, string $group): bool
    {
        $constraints = $metadata->findConstraints($group);
        foreach ($constraints as $constraint) {
            if (\get_class($constraint) === $constraintClass) {
                return true;
            }
        }

        return false;
    }
}
