<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Stub;

class TestEntity1
{
    private $field1;

    public $field2;

    /**
     * @return mixed
     */
    public function getField1()
    {
        return $this->field1;
    }

    /**
     * @param mixed $field1
     */
    public function setField1($field1)
    {
        $this->field1 = $field1;
    }
}
