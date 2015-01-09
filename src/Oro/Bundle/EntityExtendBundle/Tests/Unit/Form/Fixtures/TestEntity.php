<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Fixtures;

class TestEntity
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
