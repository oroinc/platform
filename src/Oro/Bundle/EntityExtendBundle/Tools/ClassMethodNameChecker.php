<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

class ClassMethodNameChecker
{
    public static $getters         = ['get', 'is', 'has'];
    public static $setters         = ['set'];
    public static $relationMethods = ['remove', 'setDefault', 'add'];

    /** @var \ReflectionClass[] */
    protected $reflectionCache = [];

    /**
     * @param string $property
     * @param string $className
     * @param array  $prefixes
     *
     * @return array
     */
    public function getMethods($property, $className, array $prefixes)
    {
        $suffix     = $this->camelize($property);
        $reflection = $this->getReflectionClass($className);
        $result     = [];
        foreach ($prefixes as $prefix) {
            if ($reflection->hasMethod($prefix . $suffix)) {
                $result[] = $prefix . $suffix;
            }
        }

        return $result;
    }

    /**
     *
     * @param string $string Some string
     *
     * @return string
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
