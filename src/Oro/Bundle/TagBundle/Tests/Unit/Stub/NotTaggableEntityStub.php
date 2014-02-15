<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Stub;

class NotTaggableEntityStub
{
    /**
     * @var mixed
     */
    protected $id;


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
}
