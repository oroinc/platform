<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\Common\Util\ClassUtils;

class EntityDataAccessor implements DataAccessorInterface
{
    /** @var \ReflectionClass[] */
    private $reflCache = [];

    /**
     * {@inheritdoc}
     */
    public function hasGetter($className, $property)
    {
        $reflClass = $this->getReflectionClass($className);

        $getter = $this->findGetterName($reflClass, $property);
        if ($getter) {
            return true;
        }
        if ($reflClass->hasProperty($property)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function tryGetValue($object, $property, &$value)
    {
        if (is_array($object)) {
            if (array_key_exists($property, $object)) {
                $value = $object[$property];

                return true;
            }
        } else {
            $reflClass = $this->getReflectionClass(get_class($object));

            $getter = $this->findGetterName($reflClass, $property);
            if ($getter) {
                $value = $object->{$getter}();

                return true;
            }
            if ($reflClass->hasProperty($property)) {
                $prop = $reflClass->getProperty($property);
                $prop->setAccessible(true);
                $value = $prop->getValue($object);

                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($object, $property)
    {
        $value = null;
        if (!$this->tryGetValue($object, $property, $value)) {
            if (is_array($object)) {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot get a value of "%s" field.',
                        $property
                    )
                );
            } else {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot get a value of "%s" field from "%s" entity.',
                        $property,
                        ClassUtils::getClass($object)
                    )
                );
            }
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
        return strtr(ucwords(strtr($string, ['_' => ' '])), [' ' => '']);
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

        return null;
    }

    /**
     * @param \ReflectionClass $reflClass
     * @param string           $methodName
     *
     * @return string|null
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
