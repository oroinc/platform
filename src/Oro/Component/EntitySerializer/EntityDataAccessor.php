<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * Reads property values from entity objects or arrays.
 */
class EntityDataAccessor implements DataAccessorInterface
{
    private const NO_GETTER = '';
    private const NO_PROPERTY = false;

    /** @var \ReflectionClass[] */
    private array $reflCache = [];
    /** @var array [class name => [property name => getter name, ...], ...] */
    private array $getterCache = [];
    /** @var array [class name => [property name => property, ...], ...] */
    private array $propertyCache = [];

    /**
     * {@inheritDoc}
     */
    public function hasGetter(string $className, string $property): bool
    {
        $reflClass = $this->getReflectionClass($className);

        return
            null !== $this->findGetterName($reflClass, $property)
            || null !== $this->findProperty($reflClass, $property);
    }

    /**
     * {@inheritDoc}
     */
    public function tryGetValue(object|array $object, string $property, mixed &$value): bool
    {
        if (\is_array($object)) {
            if (!\array_key_exists($property, $object)) {
                return false;
            }

            $value = $object[$property];

            return true;
        }

        $reflClass = $this->getReflectionClass(\get_class($object));
        $getter = $this->findGetterName($reflClass, $property);
        if (null !== $getter) {
            $value = $object->{$getter}();

            return true;
        }

        $propertyObject = $this->findProperty($reflClass, $property);
        if (null !== $propertyObject) {
            $value = $propertyObject->getValue($object);

            return true;
        }

        if ($object instanceof \ArrayAccess) {
            if (!$object->offsetExists($property)) {
                return false;
            }

            $value = $object->offsetGet($property);

            return true;
        }

        return false;
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

    protected function getReflectionClass(string $className): \ReflectionClass
    {
        if (isset($this->reflCache[$className])) {
            return $this->reflCache[$className];
        }

        $reflClass = $this->createReflectionClass(ClassUtils::getRealClass($className));
        $this->reflCache[$className] = $reflClass;

        return $reflClass;
    }

    protected function createReflectionClass(string $className): \ReflectionClass
    {
        return new \ReflectionClass($className);
    }

    protected function findProperty(\ReflectionClass $reflClass, string $property): ?\ReflectionProperty
    {
        if (isset($this->propertyCache[$reflClass->name][$property])) {
            $propertyObject = $this->propertyCache[$reflClass->name][$property];
        } else {
            $propertyObject = ReflectionUtil::getProperty($reflClass, $property);
            if (null === $propertyObject) {
                $propertyObject = self::NO_PROPERTY;
            } elseif (!$propertyObject->isPublic()) {
                $propertyObject->setAccessible(true);
            }
            $this->propertyCache[$reflClass->name][$property] = $propertyObject;
        }

        return self::NO_PROPERTY === $propertyObject ? null : $propertyObject;
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
        $getter = 'can' . $camelized;
        if ($this->isGetter($reflClass, $getter)) {
            return $getter;
        }
        $getter = lcfirst($camelized);
        if ($this->isGetter($reflClass, $getter)) {
            return $getter;
        }

        return self::NO_GETTER;
    }

    protected function camelize(string $string): string
    {
        return strtr(ucwords(strtr($string, ['_' => ' '])), [' ' => '']);
    }

    protected function isGetter(\ReflectionClass $reflClass, string $methodName): bool
    {
        if (!$reflClass->hasMethod($methodName)) {
            return false;
        }

        $method = $reflClass->getMethod($methodName);

        return $method->isPublic() && 0 === $method->getNumberOfRequiredParameters();
    }
}
