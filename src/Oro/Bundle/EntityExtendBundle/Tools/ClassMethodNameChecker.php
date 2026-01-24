<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

/**
 * Utility for checking and retrieving accessor methods for entity properties.
 *
 * This class provides methods to find getter, setter, and relation-specific methods
 * (like `add`, `remove`, `setDefault`) for a given property on a class. It uses reflection
 * to check if methods exist and supports common naming conventions for property accessors.
 */
class ClassMethodNameChecker
{
    public static $getters         = ['get', 'is', 'has'];
    public static $setters         = ['set'];
    public static $relationMethods = ['remove', 'setDefault', 'add'];

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
        $reflection = new \ReflectionClass($className);
        $result     = [];
        foreach ($prefixes as $prefix) {
            if ($reflection->hasMethod($prefix . $suffix)) {
                $result[] = $prefix . $suffix;
            }
        }

        return $result;
    }

    /**
     * @param string $string Some string
     *
     * @return string
     */
    protected function camelize($string)
    {
        return strtr(ucwords(strtr($string, ['_' => ' '])), [' ' => '']);
    }
}
