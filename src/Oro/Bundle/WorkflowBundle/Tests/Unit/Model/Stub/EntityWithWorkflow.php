<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub;

class EntityWithWorkflow
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
