<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit;

class ReflectionUtil
{
    /**
     * @param mixed $obj
     * @param mixed $val
     */
    public static function setId($obj, $val)
    {
        $class = new \ReflectionClass($obj);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($obj, $val);
    }
}
