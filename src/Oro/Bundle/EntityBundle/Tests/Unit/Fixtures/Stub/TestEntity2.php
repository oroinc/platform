<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Stub;

class TestEntity2
{
    private $field1;

    private $field2;

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

    /**
     * @return mixed
     */
    public function getField2()
    {
        return $this->field2;
    }

    /**
     * @param mixed $field2
     */
    public function setField2($field2)
    {
        $this->field2 = $field2;
    }
}
