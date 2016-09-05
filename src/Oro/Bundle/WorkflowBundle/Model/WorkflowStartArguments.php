<?php

namespace Oro\Bundle\WorkflowBundle\Model;

class WorkflowStartArguments
{
    /** @var Workflow */
    private $workflowName;

    /** @var object */
    private $entity;

    /** @var array */
    private $data;

    /** @var string */
    private $transition;

    /**
     * @param string $workflowName
     * @param object $entity
     * @param array $data
     * @param string|null $transition
     */
    public function __construct($workflowName, $entity, array $data = [], $transition = null)
    {
        $this->workflowName = (string)$workflowName;
        $this->entity = $entity;
        $this->data = $data;
        $this->transition = $transition;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return Workflow
     */
    public function getWorkflowName()
    {
        return $this->workflowName;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getTransition()
    {
        return $this->transition;
    }
}
