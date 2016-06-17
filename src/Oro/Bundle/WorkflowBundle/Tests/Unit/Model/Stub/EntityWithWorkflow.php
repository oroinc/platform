<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareTrait;

class EntityWithWorkflow implements WorkflowAwareInterface
{
    use WorkflowAwareTrait;
    
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
