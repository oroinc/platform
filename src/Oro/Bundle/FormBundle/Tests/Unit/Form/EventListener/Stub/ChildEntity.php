<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\EventListener\Stub;

class ChildEntity
{
    protected $name;
    protected $parent;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }
}
