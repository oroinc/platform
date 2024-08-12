<?php

namespace Oro\Bundle\CacheBundle\Serializer\Normalizer;

use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Serializer\Annotation\Ignore as LegacyIgnore;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer as BaseGetSetMethodNormalizer;

/**
 * Modified normalizer to handle(skip) exception thrown during reading of object's attribute values.
 */
class GetSetMethodNormalizer extends BaseGetSetMethodNormalizer
{
    /**
     * {@inheritDoc}
     */
    protected function getAttributeValue(
        object $object,
        string $attribute,
        string $format = null,
        array $context = []
    ): mixed {
        $value = parent::getAttributeValue($object, $attribute, $format, $context);

        try {
            $value = $value ?: (\is_callable([$object, '__get']) ? $object->__get($attribute) : null);
        } catch (\Exception $e) {
        }

        return $value;
    }

    protected function setAttributeValue(
        object $object,
        string $attribute,
        mixed $value,
        ?string $format = null,
        array $context = []
    ): void {
        parent::setAttributeValue($object, $attribute, $value, $format, $context);
        $setter = 'set'.$attribute;

        if (!method_exists($object, $setter)  && (\is_callable([$object, '__set']))) {
            $object->__set($attribute, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function isAllowedAttribute(
        $classOrObject,
        string $attribute,
        ?string $format = null,
        array $context = []
    ) {
        $class = \is_object($classOrObject) ? \get_class($classOrObject) : $classOrObject;

        if (ExtendHelper::isExtendEntity($class)) {
            return  $this->isAllowedExtendEntityAttribute($class, $attribute, $context);
        }

        return parent::isAllowedAttribute($classOrObject, $attribute, $format, $context);
    }

    /**
     * Check if exist own and virtual method and properties with ExtendEntity
     */
    private function isAllowedExtendEntityAttribute(
        string $class,
        string $attribute,
        array $context = []
    ) {
        $reflection = new EntityReflectionClass($class);

        if ($context['_read_attributes'] ?? true) {
            foreach (['get', 'is', 'has'] as $getterPrefix) {
                $getter = $getterPrefix.$attribute;
                $reflectionMethod = $reflection->hasMethod($getter) ? $reflection->getMethod($getter) : null;

                if ($reflectionMethod && $this->isGetMethod($reflectionMethod)) {
                    return true;
                }
            }

            return false;
        }

        $setter = 'set'.$attribute;
        if ($reflection->hasMethod($setter) && $this->isSetMethod($reflection->getMethod($setter))) {
            return true;
        }

        if ($this->isAllowedExtendEntityConstructorAttribute($reflection, $attribute)) {
            return true;
        }

        return false;
    }

    /**
     * Check if attribute exist in constructor
     */
    private function isAllowedExtendEntityConstructorAttribute(
        EntityReflectionClass $reflection,
        string $attribute
    ): ?bool {
        $constructor = $reflection->getConstructor();

        if ($constructor && $constructor->isPublic()) {
            foreach ($constructor->getParameters() as $parameter) {
                if ($parameter->getName() === $attribute) {
                    return true;
                }
            }
        }

        return null;
    }

    /**
     * Checks if a method's can be called non-statically without parameters.
     */
    private function isGetMethod(\ReflectionMethod $method): bool
    {
        return 'void' !== $method->getReturnType()?->getName()
            && !$method->isStatic()
            && !($method->getAttributes(Ignore::class) || $method->getAttributes(LegacyIgnore::class))
            && !$method->getNumberOfRequiredParameters();
    }

    /**
     * Checks if a method's name matches /^set.+$/ and can be called non-statically with one parameter.
     */
    private function isSetMethod(\ReflectionMethod $method): bool
    {
        return !$method->isStatic()
            && !$method->getAttributes(Ignore::class)
            && 1 === $method->getNumberOfRequiredParameters()
            && (str_starts_with($method->name, 'set') || ($method->name === 'get'));
    }

    protected function extractAttributes(object $object, ?string $format = null, array $context = []): array
    {
        $reflectionObject = new EntityReflectionClass($object);
        $reflectionMethods = $reflectionObject->getMethods(\ReflectionMethod::IS_PUBLIC);

        $attributes = [];
        foreach ($reflectionMethods as $method) {
            if (!$this->isGetMethod($method)) {
                continue;
            }

            $attributeName = lcfirst(substr(
                $method->getName(),
                str_starts_with($method->getName(), 'is') ? 2 : 3
            ));

            if ($this->isAllowedAttribute($object, $attributeName, $format, $context)) {
                $attributes[] = $attributeName;
            }
        }

        return $attributes;
    }
}
