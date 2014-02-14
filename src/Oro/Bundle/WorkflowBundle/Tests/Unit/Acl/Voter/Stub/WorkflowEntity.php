<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Voter\Stub;

class WorkflowEntity
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
