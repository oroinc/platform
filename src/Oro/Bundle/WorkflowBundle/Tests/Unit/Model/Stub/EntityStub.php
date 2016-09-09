<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub;

class EntityStub
{
    /** @var mixed */
    private $id;

    /**
     * EntityStub constructor.
     * @param mixed $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
