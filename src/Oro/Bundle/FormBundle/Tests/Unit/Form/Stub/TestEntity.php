<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Stub;

class TestEntity
{
    protected $id;

    public function __construct($id = null)
    {
        $this->id = $id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}
