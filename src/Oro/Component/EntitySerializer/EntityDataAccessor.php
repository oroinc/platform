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
    private $reflCache = [];

    /** @var array [class name => [property name => getter name, ...], ...] */
    private $getterCache = [];

    /**
     * {@inheritdoc}
     */
    public function hasGetter($className, $property)
    {
        $reflClass = $this->getReflectionClass($className);
        $getter = $this->findGetterName($reflClass, $property);

        return $getter || $reflClass->hasProperty($property);
    }

    /**
     * {@inheritdoc}
     */
    public function tryGetValue($object, $property, &$value)
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
     * {@inheritdoc}
     */
    public function getValue($object, $property)
    {
        $value = null;
        if (!$this->tryGetValue($object, $property, $value)) {
            $message = \is_array($object)
                ? \sprintf('Cannot get a value of "%s" field.', $property)
                : \sprintf(
                    'Cannot get a value of "%s" field from "%s" entity.',
                    $property,
                    ClassUtils::getClass($object)
                );
            throw new \RuntimeException($message);
        };

        return $value;
    }

    /**
     * Camelizes a given string.
     *
     * @param string $string Some string
     *
     * @return string The camelized version of the string
     */
    protected function camelize($string)
    {
        return \strtr(\ucwords(\strtr($string, ['_' => ' '])), [' ' => '']);
    }

    /**
     * Gets an instance of \ReflectionClass for the given class name
     *
     * @param string $className
     *
     * @return \ReflectionClass
     */
    protected function getReflectionClass($className)
    {
        if (isset($this->reflCache[$className])) {
            return $this->reflCache[$className];
        }

        $reflClass = new \ReflectionClass($className);
        $this->reflCache[$className] = $reflClass;

        return $reflClass;
    }

    /**
     * @param \ReflectionClass $reflClass
     * @param string           $property
     *
     * @return string|null
     */
    protected function findGetterName(\ReflectionClass $reflClass, $property)
    {
        if (isset($this->getterCache[$reflClass->name][$property])) {
            $getterName = $this->getterCache[$reflClass->name][$property];
        } else {
            $getterName = $this->getGetterName($reflClass, $property);
            $this->getterCache[$reflClass->name][$property] = $getterName;
        }

        return self::NO_GETTER === $getterName ? null : $getterName;
    }

    /**
     * @param \ReflectionClass $reflClass
     * @param string           $property
     *
     * @return string
     */
    protected function getGetterName(\ReflectionClass $reflClass, $property)
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
        $getter = \lcfirst($camelized);
        if ($this->isGetter($reflClass, $getter)) {
            return $getter;
        }

        return self::NO_GETTER;
    }

    /**
     * @param \ReflectionClass $reflClass
     * @param string           $methodName
     *
     * @return bool
     */
    protected function isGetter(\ReflectionClass $reflClass, $methodName)
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
