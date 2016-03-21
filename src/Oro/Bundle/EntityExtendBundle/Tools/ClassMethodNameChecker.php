<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

class ClassMethodNameChecker
{
    /** @var \ReflectionClass[] */
    protected $reflectionCache = [];

    /** @var array */
    protected $gettersPrefix = ['get', 'is', 'has'];

    /** @var array */
    protected $settersPrefix = ['set', 'setDefault', 'add'];

    /** @var array */
    protected $removerPrefix = ['remove'];

    /**
     * @param string $className
     * @param string $property
     * @param bool   $checkGetters
     * @param bool   $checkSetters
     * @param bool   $checkRemovers
     *
     * @return string
     */
    public function getConflictMethodName(
        $className,
        $property,
        $checkGetters = true,
        $checkSetters = true,
        $checkRemovers = true
    ) {
        if ($checkGetters) {
            $result = $this->checkMethod($property, $className, $this->gettersPrefix);

            if (!empty($result)) {
                return $result;
            }
        }

        if ($checkSetters) {
            $result = $this->checkMethod($property, $className, $this->settersPrefix);

            if (!empty($result)) {
                return $result;
            }
        }

        if ($checkRemovers) {
            $result = $this->checkMethod($property, $className, $this->removerPrefix);

            if (!empty($result)) {
                return $result;
            }
        }

        return $this->checkMethod($property, $className, ['']);
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
