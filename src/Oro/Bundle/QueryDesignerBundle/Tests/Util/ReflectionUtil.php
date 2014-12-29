<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Util;

class ReflectionUtil
{
    public static function callProtectedMethod($obj, $method, $parameters = [])
    {
        $reflObj    = new \ReflectionObject($obj);
        $reflMethod = $reflObj->getMethod($method);
        $reflMethod->setAccessible(true);

        return $reflMethod->invokeArgs($obj, $parameters);
    }
}
