<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type\Stub;

class TestEntity
{
    protected $id;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}
