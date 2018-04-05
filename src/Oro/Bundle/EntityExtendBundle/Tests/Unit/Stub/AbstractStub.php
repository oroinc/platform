<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Stub;

abstract class AbstractStub
{
    /** @var array */
    protected static $expected = [];

    /** @var array */
    protected static $calls = [];

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, array $arguments)
    {
        $class = get_called_class();

        self::$calls[$class][$name][] = $arguments;

        $arguments = serialize($arguments);

        $result = null;
        if (isset(self::$expected[$class][$name][$arguments])) {
            $result = array_shift(self::$expected[$class][$name][$arguments]);
        }

        return $result;
    }

    /**
     * @param string $method
     * @param array $arguments
     * @param mixed $result
     */
    public static function addExpectedCall($method, array $arguments, $result)
    {
        self::$expected[get_called_class()][$method][serialize($arguments)][] = $result;
    }

    /**
     * @return array
     */
    public static function getCalls()
    {
        $class = get_called_class();
        $calls = self::$calls[$class] ?? [];

        self::$calls[$class] = [];

        return $calls;
    }

    public static function clearCalls()
    {
        self::$calls[get_called_class()] = [];
    }
}
