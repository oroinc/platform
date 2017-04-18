<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Stub;

use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class EmailNotificationStub extends EmailNotification
{
    /** @var WorkflowDefinition */
    protected $workflowDefinition;

    /** @var string */
    protected $transitionName;

    /**
     * @param string $workflowName
     * @param string $transitionName
     */
    public function __construct($workflowName = null, $transitionName = null)
    {
        if ($workflowName) {
            $this->workflowDefinition = new WorkflowDefinition();
            $this->workflowDefinition->setName($workflowName);
        }

        $this->transitionName = $transitionName;
    }

    /**
     * @return WorkflowDefinition
     */
    public function getWorkflowDefinition()
    {
        return $this->workflowDefinition;
    }

    /**
     * @return string
     */
    public function getWorkflowTransitionName()
    {
        return $this->transitionName;
    }
}
