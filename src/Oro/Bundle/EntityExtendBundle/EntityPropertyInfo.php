<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle;

use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendedEntityFieldsProcessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Entity property info helper.
 */
class EntityPropertyInfo
{
    public static function propertyExists(string|object $objectOrClass, $property): bool
    {
        if ($property instanceof PropertyPathInterface) {
            $property = $property->getElement(0);
        }
        if (property_exists($objectOrClass, $property)) {
            return true;
        }

        if (is_subclass_of($objectOrClass, ExtendEntityInterface::class)) {
            $transport = self::createTransport($objectOrClass);
            $transport->setValue(EntityFieldProcessTransport::EXISTS_PROPERTY);
            $transport->setName($property);

            ExtendedEntityFieldsProcessor::executePropertyExists($transport);

            if ($transport->isProcessed() && $transport->getResult()) {
                return true;
            }
        }

        return false;
    }

    public static function getExtendedProperties(object|string $objectOrClass): array
    {
        if (is_subclass_of($objectOrClass, ExtendEntityInterface::class)) {
            if (is_object($objectOrClass)) {
                $objectOrClass = $objectOrClass::class;
            }
            $entityMetadata = ExtendedEntityFieldsProcessor::getEntityMetadata($objectOrClass);
            if (null === $entityMetadata) {
                return [];
            }
            $result = array_merge(
                array_keys($entityMetadata->get('schema')['property']),
                array_keys($entityMetadata->get('schema')['relation']),
            );

            return $result;
        }

        return [];
    }

    public static function methodExists(string|object $objectOrClass, string $method): bool
    {
        if (method_exists($objectOrClass, $method)) {
            return true;
        }

        return self::extendedMethodExists($objectOrClass, $method);
    }

    public static function extendedMethodExists(object|string $objectOrClass, string $method): bool
    {
        if (is_subclass_of($objectOrClass, ExtendEntityInterface::class)) {
            $transport = self::createTransport($objectOrClass);
            $transport->setValue(EntityFieldProcessTransport::EXISTS_METHOD);
            $transport->setName($method);

            ExtendedEntityFieldsProcessor::executeMethodExists($transport);

            if ($transport->isProcessed() && $transport->getResult()) {
                return true;
            }
        }

        return false;
    }

    public static function getExtendedMethods(object|string $objectOrClass): array
    {
        if (!is_subclass_of($objectOrClass, ExtendEntityInterface::class)) {
            return [];
        }

        return ExtendedEntityFieldsProcessor::getMethods(self::createTransport($objectOrClass));
    }

    private static function createTransport(object|string $objectOrClass): EntityFieldProcessTransport
    {
        $transport = new EntityFieldProcessTransport();
        if (is_object($objectOrClass)) {
            $transport->setClass($objectOrClass::class);
        } else {
            $transport->setClass($objectOrClass);
        }

        return $transport;
    }

    public static function isMethodMatchExists(array $methodList, string $method): bool
    {
        $lowerMethods = array_map('strtolower', $methodList);

        return in_array(strtolower($method), $lowerMethods);
    }

    public static function getMatchedMethod(string $class, string $originalMethod): string
    {
        $extendMethods = array_keys(self::getExtendedMethods($class));
        foreach ($extendMethods as $method) {
            if (strtolower($method) === strtolower($originalMethod)) {
                return $method;
            }
        }

        return $originalMethod;
    }
}
