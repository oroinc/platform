<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

class ClassMethodNameChecker
{
    /** @var \ReflectionClass[] */
    protected $reflectionCache = [];

    /** @var array */
    protected $gettersPrefix = ['get', 'is', 'has'];

    /** @var array */
    protected $settersPrefix = ['set'];

    /** @var array */
    protected $relationPrefix = ['remove', 'setDefault', 'add'];

    /**
     * @param string $className
     * @param string $property
     *
     * @return string
     */
    public function getGetters($className, $property)
    {
        return $this->checkMethod($property, $className, $this->gettersPrefix);
    }

    /**
     * @param string $className
     * @param string $property
     *
     * @return string
     */
    public function getSetters($className, $property)
    {
        return $this->checkMethod($property, $className, $this->settersPrefix);
    }

    /**
     * @param string $className
     * @param string $property
     *
     * @return string
     */
    public function getRelationMethods($className, $property)
    {
        return $this->checkMethod($property, $className, $this->relationPrefix);
    }

    /**
     * @param string $property
     * @param string $className
     * @param array  $searching
     *
     * @return string
     */
    protected function checkMethod($property, $className, array $searching)
    {
        $suffix     = $this->camelize($property);
        $reflection = $this->getReflectionClass($className);

        foreach ($searching as $prefix) {
            if ($reflection->hasMethod($prefix . $suffix)) {
                return $prefix . $suffix;
            }
        }

        return '';
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
