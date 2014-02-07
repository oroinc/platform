<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub;

class EntityStub
{
    protected $id;

    public $parent;

    public function __construct($id = null, EntityStub $parent = null)
    {
        $this->id = $id;
        $this->parent = $parent;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getParentId()
    {
        return $this->parent->getId();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setParentId($id)
    {
        $this->parent->setId($id);
    }
}
