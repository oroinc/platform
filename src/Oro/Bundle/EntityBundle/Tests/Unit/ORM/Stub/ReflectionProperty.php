<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub;

class ReflectionProperty extends \ReflectionProperty
{
    protected $values = [];

    public function __construct($class, $name, array $values = [])
    {
        $this->values = $values;
    }

    public function getValue($object = null)
    {
        return $this->values[spl_object_hash($object)];
    }
}
