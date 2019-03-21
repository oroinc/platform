<?php

namespace Oro\Bundle\SecurityBundle\Tests\Util;

class ReflectionUtil
{
    /**
     * @param mixed  $obj
     * @param string $methodName
     * @param array  $args
     *
     * @return mixed
     */
    public static function callProtectedMethod($obj, $methodName, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
