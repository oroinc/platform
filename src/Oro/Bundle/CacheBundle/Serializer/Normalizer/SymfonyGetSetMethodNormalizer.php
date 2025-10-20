<?php

namespace Oro\Bundle\CacheBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Annotation\Ignore as LegacyIgnore;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Modified copy of { @see \Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer }
 * Removed the `final` modifier from the class declaration to allow inheritance
 *
 * Copyright (c) 2004-present Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 *
 * Converts between objects with getter and setter methods and arrays.
 *
 * The normalization process looks at all public methods and calls the ones
 * which have a name starting with get and take no parameters. The result is a
 * map from property names (method name stripped of the get prefix and converted
 * to lower case) to property values. Property values are normalized through the
 * serializer.
 *
 * The denormalization first looks at the constructor of the given class to see
 * if any of the parameters have the same name as one of the properties. The
 * constructor is then called with all parameters or an exception is thrown if
 * any required parameters were not present as properties. Then the denormalizer
 * walks through the given map of property names to property values to see if a
 * setter method exists for any of the properties. If a setter exists it is
 * called with the property value. No automatic denormalization of the value
 * takes place.
 *
 * @author Nils Adermann <naderman@naderman.de>
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SymfonyGetSetMethodNormalizer extends AbstractObjectNormalizer
{
    private static $reflectionCache = [];
    private static array $setterAccessibleCache = [];

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return parent::supportsNormalization($data, $format) && $this->supports($data::class, true);
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return parent::supportsDenormalization($data, $type, $format) && $this->supports($type, false);
    }

    /**
     * Checks if the given class has any getter or setter method.
     */
    private function supports(string $class, bool $readAttributes): bool
    {
        if ($this->classDiscriminatorResolver?->getMappingForClass($class)) {
            return true;
        }

        if (!isset(self::$reflectionCache[$class])) {
            self::$reflectionCache[$class] = new \ReflectionClass($class);
        }

        $reflection = self::$reflectionCache[$class];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            if ($readAttributes ? $this->isGetMethod($reflectionMethod) : $this->isSetMethod($reflectionMethod)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a method's name matches /^(get|is|has).+$/ and can be called non-statically without parameters.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function isGetMethod(\ReflectionMethod $method): bool
    {
        return !$method->isStatic()
            && !($method->getAttributes(Ignore::class) || $method->getAttributes(LegacyIgnore::class))
            && !$method->getNumberOfRequiredParameters()
            && (
                (2 < ($methodLength = \strlen($method->name))
                    && str_starts_with($method->name, 'is')
                    && !ctype_lower($method->name[2]))
                || (3 < $methodLength && (str_starts_with($method->name, 'has')
                || str_starts_with($method->name, 'get')) && !ctype_lower($method->name[3]))
            );
    }

    /**
     * Checks if a method's name matches /^set.+$/ and can be called non-statically with one parameter.
     */
    private function isSetMethod(\ReflectionMethod $method): bool
    {
        return !$method->isStatic()
            && !$method->getAttributes(Ignore::class)
            && 0 < $method->getNumberOfParameters()
            && 3 < \strlen($method->name)
            && str_starts_with($method->name, 'set')
            && !ctype_lower($method->name[3])
        ;
    }

    protected function extractAttributes(object $object, ?string $format = null, array $context = []): array
    {
        $reflectionObject = new \ReflectionObject($object);
        $reflectionMethods = $reflectionObject->getMethods(\ReflectionMethod::IS_PUBLIC);

        $attributes = [];
        foreach ($reflectionMethods as $method) {
            if (!$this->isGetMethod($method)) {
                continue;
            }

            $attributeName = lcfirst(substr($method->name, str_starts_with($method->name, 'is') ? 2 : 3));

            if ($this->isAllowedAttribute($object, $attributeName, $format, $context)) {
                $attributes[] = $attributeName;
            }
        }

        return $attributes;
    }

    protected function getAttributeValue(
        object $object,
        string $attribute,
        ?string $format = null,
        array $context = []
    ): mixed {
        $getter = 'get'.$attribute;
        if (method_exists($object, $getter) && \is_callable([$object, $getter])) {
            return $object->$getter();
        }

        $isser = 'is'.$attribute;
        if (method_exists($object, $isser) && \is_callable([$object, $isser])) {
            return $object->$isser();
        }

        $haser = 'has'.$attribute;
        if (method_exists($object, $haser) && \is_callable([$object, $haser])) {
            return $object->$haser();
        }

        return null;
    }

    protected function setAttributeValue(
        object $object,
        string $attribute,
        mixed $value,
        ?string $format = null,
        array $context = []
    ): void {
        $setter = 'set'.$attribute;
        $key = $object::class.':'.$setter;

        if (!isset(self::$setterAccessibleCache[$key])) {
            self::$setterAccessibleCache[$key] = method_exists($object, $setter)
                && \is_callable([$object, $setter])
                && !(new \ReflectionMethod($object, $setter))->isStatic();
        }

        if (self::$setterAccessibleCache[$key]) {
            $object->$setter($value);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function isAllowedAttribute(
        $classOrObject,
        string $attribute,
        ?string $format = null,
        array $context = []
    ): bool {
        if (!parent::isAllowedAttribute($classOrObject, $attribute, $format, $context)) {
            return false;
        }

        $class = \is_object($classOrObject) ? $classOrObject::class : $classOrObject;

        if (!isset(self::$reflectionCache[$class])) {
            self::$reflectionCache[$class] = new \ReflectionClass($class);
        }

        $reflection = self::$reflectionCache[$class];

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

        $constructor = $reflection->getConstructor();

        if ($constructor && $constructor->isPublic()) {
            foreach ($constructor->getParameters() as $parameter) {
                if ($parameter->getName() === $attribute) {
                    return true;
                }
            }
        }

        return false;
    }
}
