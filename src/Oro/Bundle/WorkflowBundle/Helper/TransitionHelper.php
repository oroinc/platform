<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Doctrine\Common\Collections\ArrayCollection;

class TransitionHelper
{
    /** @var ArrayCollection */
    private $transitionsIdentifiers;

    public function __construct()
    {
        $this->transitionsIdentifiers = new ArrayCollection();
    }

    /**
     * @param object $entity
     * @param string $workflowName
     *
     * @return bool
     */
    public function isStartedWorkflowTransition($entity, $workflowName)
    {
        return $this->transitionsIdentifiers->contains(
            $this->getEntityWorkflowIdentifier($entity, $workflowName)
        );
    }

    /**
     * @param object $entity
     * @param string $workflowName
     */
    public function addWorkflowTransition($entity, $workflowName)
    {
        $this->transitionsIdentifiers->add(
            $this->getEntityWorkflowIdentifier($entity, $workflowName)
        );
    }

    /**
     * @param object $entity
     * @param string $workflowName
     */
    public function removeWorkflowTransition($entity, $workflowName)
    {
        $this->transitionsIdentifiers->removeElement(
            $this->getEntityWorkflowIdentifier($entity, $workflowName)
        );
    }

    /**
     * Returns an unique identifier for the entity with workflow
     *
     * @param object $entity
     * @param string $workflowName
     *
     * @return string
     */
    private function getEntityWorkflowIdentifier($entity, $workflowName)
    {
        return sprintf("%s-%s", $workflowName, spl_object_hash($entity));
    }
}
