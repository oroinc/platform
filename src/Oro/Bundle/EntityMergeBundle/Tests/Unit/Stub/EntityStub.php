<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub;

use Doctrine\Common\Collections\ArrayCollection;

class EntityStub
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var EntityStub
     */
    public $parent;

    /**
     * @var ArrayCollection
     */
    public $collection;

    public function __construct($id = null, EntityStub $parent = null)
    {
        $this->id         = $id;
        $this->parent     = $parent;
        $this->collection = new ArrayCollection();
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

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $collection
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;
    }

    /**
     * @param mixed $item
     */
    public function addCollectionItem($item)
    {
        $this->collection[] = $item;
    }
}
