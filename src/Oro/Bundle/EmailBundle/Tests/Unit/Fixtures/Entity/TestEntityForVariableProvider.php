<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

class TestEntityForVariableProvider
{
    protected $field1;

    protected $field2;

    protected $field3;

    protected $field4;

    public $field5;

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
    public function getField1()
    {
        return $this->field1;
    }

    /**
     * @param mixed $field2
     */
    public function setField2($field2)
    {
        $this->field2 = $field2;
    }

    /**
     * @return mixed
     */
    public function getField2()
    {
        return $this->field2;
    }

    /**
     * @param mixed $field3
     */
    public function setField3($field3)
    {
        $this->field3 = $field3;
    }

    /**
     * @return mixed
     */
    public function isField3()
    {
        return $this->field3;
    }
}
