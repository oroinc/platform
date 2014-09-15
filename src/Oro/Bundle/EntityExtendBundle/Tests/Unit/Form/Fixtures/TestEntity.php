<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Fixtures;

class TestEntity
{
    protected $id;

    public function __construct($id = null)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}
