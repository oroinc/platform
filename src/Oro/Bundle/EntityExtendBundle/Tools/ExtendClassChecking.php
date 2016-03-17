<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

class ExtendClassChecking
{
    /** @var \ReflectionClass[] */
    protected $reflectionCache = [];

    /**
     * @param string $className
     * @param string $property
     *
     * @return boolean
     */
    public function hasGetter($className, $property)
    {
        $suffix     = $this->camelize($property);
        $reflection = $this->getReflectionClass($className);

        if ($reflection->hasMethod('get' . $suffix)) {
            return true;
        }
        if ($reflection->hasMethod('is' . $suffix)) {
            return true;
        }
        if ($reflection->hasMethod('has' . $suffix)) {
            return true;
        }
        if ($reflection->hasMethod($suffix)) {
            return true;
        }
        if ($reflection->hasProperty($property)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $className
     * @param string $property
     *
     * @return boolean
     */
    public function hasSetter($className, $property)
    {
        $suffix     = $this->camelize($property);
        $reflection = $this->getReflectionClass($className);

        if ($reflection->hasMethod('set' . $suffix)) {
            return true;
        }
        if ($reflection->hasMethod('setDefault' . $suffix)) {
            return true;
        }
        if ($reflection->hasMethod('add' . $suffix)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $className
     * @param string $property
     *
     * @return boolean
     */
    public function hasRemover($className, $property)
    {
        $suffix     = $this->camelize($property);
        $reflection = $this->getReflectionClass($className);

        if ($reflection->hasMethod('remove' . $suffix)) {
            return true;
        }

        return false;
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
        if (isset($this->reflectionCache[$className])) {
            return $this->reflectionCache[$className];
        }

        $reflectionClass                   = new \ReflectionClass($className);
        $this->reflectionCache[$className] = $reflectionClass;

        return $reflectionClass;
    }
}
