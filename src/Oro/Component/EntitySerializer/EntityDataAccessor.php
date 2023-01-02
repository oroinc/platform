<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\Common\Util\ClassUtils;

/**
 * Reads property values from entity objects or arrays.
 */
class EntityDataAccessor implements DataAccessorInterface
{
    private const NO_GETTER = '';

    /** @var \ReflectionClass[] */
    private array $reflCache = [];
    /** @var array [class name => [property name => getter name, ...], ...] */
    private array $getterCache = [];

    /**
     * {@inheritDoc}
     */
    public function hasGetter(string $className, string $property): bool
    {
        $reflClass = $this->getReflectionClass($className);
        $getter = $this->findGetterName($reflClass, $property);

        return $getter || $reflClass->hasProperty($property);
    }

    /**
     * {@inheritDoc}
     */
    public function tryGetValue(object|array $object, string $property, mixed &$value): bool
    {
        $hasValue = false;
        if (\is_array($object)) {
            if (\array_key_exists($property, $object)) {
                $hasValue = true;
                $value = $object[$property];
            }
        } else {
            $reflClass = $this->getReflectionClass(\get_class($object));
            $getter = $this->findGetterName($reflClass, $property);
            if ($getter) {
                $hasValue = true;
                $value = $object->{$getter}();
            } elseif ($reflClass->hasProperty($property)) {
                $hasValue = true;
                $prop = $reflClass->getProperty($property);
                $prop->setAccessible(true);
                $value = $prop->getValue($object);
            }
        }

        return $hasValue;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(object|array $object, string $property): mixed
    {
        $value = null;
        if (!$this->tryGetValue($object, $property, $value)) {
            $message = \is_array($object)
                ? sprintf('Cannot get a value of "%s" field.', $property)
                : sprintf(
                    'Cannot get a value of "%s" field from "%s" entity.',
                    $property,
                    ClassUtils::getClass($object)
                );
            throw new \RuntimeException($message);
        }

        return $value;
    }

    protected function camelize(string $string): string
    {
        return strtr(ucwords(strtr($string, ['_' => ' '])), [' ' => '']);
    }

    protected function getReflectionClass(string $className): \ReflectionClass
    {
        if (isset($this->reflCache[$className])) {
            return $this->reflCache[$className];
        }

        $reflClass = new \ReflectionClass($className);
        $this->reflCache[$className] = $reflClass;

        return $reflClass;
    }

    protected function findGetterName(\ReflectionClass $reflClass, string $property): ?string
    {
        if (isset($this->getterCache[$reflClass->name][$property])) {
            $getterName = $this->getterCache[$reflClass->name][$property];
        } else {
            $getterName = $this->getGetterName($reflClass, $property);
            $this->getterCache[$reflClass->name][$property] = $getterName;
        }

        return self::NO_GETTER === $getterName ? null : $getterName;
    }

    protected function getGetterName(\ReflectionClass $reflClass, string $property): string
    {
        $camelized = $this->camelize($property);

        $getter = 'get' . $camelized;
        if ($this->isGetter($reflClass, $getter)) {
            return $getter;
        }
        $getter = 'is' . $camelized;
        if ($this->isGetter($reflClass, $getter)) {
            return $getter;
        }
        $getter = 'has' . $camelized;
        if ($this->isGetter($reflClass, $getter)) {
            return $getter;
        }
        $getter = lcfirst($camelized);
        if ($this->isGetter($reflClass, $getter)) {
            return $getter;
        }

        return self::NO_GETTER;
    }

    protected function isGetter(\ReflectionClass $reflClass, string $methodName): bool
    {
        if (!$reflClass->hasMethod($methodName)) {
            return false;
        }

        $method = $reflClass->getMethod($methodName);

        return
            $method->isPublic()
            && 0 === $method->getNumberOfRequiredParameters();
    }
}
