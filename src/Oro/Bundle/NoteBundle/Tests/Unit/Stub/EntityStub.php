<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Stub;

class EntityStub
{
    /** @var mixed */
    protected $id;

    /**
     * EntityStub constructor.
     * @param null $id
     */
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
