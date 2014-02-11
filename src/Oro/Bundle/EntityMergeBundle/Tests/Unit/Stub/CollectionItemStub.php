<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub;

class CollectionItemStub
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var EntityStub
     */
    protected $entityStub;

    public function __construct($id = null)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return EntityStub
     */
    public function getEntityStub()
    {
        return $this->entityStub;
    }

    /**
     * @param EntityStub $entityStub
     */
    public function setEntityStub($entityStub)
    {
        $this->entityStub = $entityStub;
    }
}
