<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Validator\Mapping;

use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\ReflectionVirtualProperty;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendedEntityFieldsProcessor;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Mapping\MemberMetadata;

/**
 * Stores all metadata needed for validating a class property.
 *
 * @see \Symfony\Component\Validator\Mapping\PropertyMetadata
 */
class PropertyMetadata extends MemberMetadata
{
    private const PROPERTY_NOT_EXISTS = 0;
    private const PROPERTY_REAL       = 1;
    private const PROPERTY_VIRTUAL    = 2;

    /**
     * @inheritDoc
     */
    public function __construct(string $class, string $name)
    {
        if (self::PROPERTY_NOT_EXISTS === $this->getPropertyType($class, $name)) {
            throw new ValidatorException(sprintf('Property "%s" does not exist in class "%s".', $name, $class));
        }

        parent::__construct($class, $name, $name);
    }

    /**
     * @inheritDoc
     */
    protected function newReflectionMember($objectOrClassName)
    {
        $className = \is_string($objectOrClassName) ? $objectOrClassName : \get_class($objectOrClassName);
        $propertyType = $this->getPropertyType($className, $this->getName());
        if ($propertyType === self::PROPERTY_NOT_EXISTS) {
            throw new ValidatorException(
                sprintf('Property "%s" does not exist in class "%s".', $this->getName(), $className)
            );
        }

        if ($propertyType === self::PROPERTY_REAL) {
            $className = $this->lookupClassProperty($className, $this->getName());
            $member = new \ReflectionProperty($className, $this->getName());
        } else {
            $member = ReflectionVirtualProperty::create($this->getName());
        }
        $member->setAccessible(true);

        return $member;
    }

    private function lookupClassProperty(string $className, string $propertyName): ?string
    {
        $isMatch = true;
        while (!property_exists($className, $propertyName)) {
            $className = get_parent_class($className);

            if (false === $className) {
                $isMatch = false;
                break;
            }
        }
        if ($isMatch) {
            return $className;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getPropertyValue($containingValue)
    {
        $reflProperty = $this->getReflectionMember($containingValue);

        if (\PHP_VERSION_ID >= 70400 && $reflProperty->hasType() && !$reflProperty->isInitialized($containingValue)) {
            // There is no way to check if a property has been unset or if it is uninitialized.
            // When trying to access an uninitialized property, __get method is triggered.

            // If __get method is not present, no fallback is possible
            // Otherwise we need to catch an Error in case we are trying to access an uninitialized but set property.
            if (!method_exists($containingValue, '__get')) {
                return null;
            }

            try {
                return $reflProperty->getValue($containingValue);
            } catch (\Error $e) {
                return null;
            }
        }

        return $reflProperty->getValue($containingValue);
    }

    private function getPropertyType(string $className, string $propertyName): int
    {
        if ($this->lookupClassProperty($className, $propertyName)) {
            return self::PROPERTY_REAL;
        }

        if (is_subclass_of($className, ExtendEntityInterface::class)) {
            $transport = new EntityFieldProcessTransport();
            $transport->setClass($className);
            $transport->setName($propertyName);
            $transport->setValue(EntityFieldProcessTransport::EXISTS_PROPERTY);

            ExtendedEntityFieldsProcessor::executePropertyExists($transport);

            if ($transport->isProcessed() && $transport->getResult()) {
                return self::PROPERTY_VIRTUAL;
            }
        }

        return self::PROPERTY_NOT_EXISTS;
    }
}
