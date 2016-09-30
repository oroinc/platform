<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityNotFoundException;

use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionEventTriggerHandler implements TransitionTriggerHandlerInterface
{
    /** @var WorkflowManager */
    private $workflowManager;

    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param WorkflowManager $workflowManager
     * @param ManagerRegistry $registry
     */
    public function __construct(WorkflowManager $workflowManager, ManagerRegistry $registry)
    {
        $this->workflowManager = $workflowManager;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(BaseTransitionTrigger $trigger, TransitionTriggerMessage $message)
    {
        $mainEntity = $this->resolveEntity(
            $trigger->getWorkflowDefinition()->getRelatedEntity(),
            $message->getMainEntityId()
        );

        $workflowItem = $this->workflowManager->getWorkflowItem($mainEntity, $trigger->getWorkflowName());

        if ($workflowItem) {
            return $this->workflowManager->transitIfAllowed($workflowItem, $trigger->getTransitionName());
        } else {
            return (bool)$this->workflowManager->startWorkflow(
                $trigger->getWorkflowName(),
                $mainEntity,
                $trigger->getTransitionName(),
                [],
                false
            );
        }
    }

    /**
     * @param string $className
     * @param int|array $id
     * @return null|object
     * @throws \InvalidArgumentException|EntityNotFoundException
     */
    protected function resolveEntity($className, $id)
    {
        if (!$id) {
            throw new \InvalidArgumentException(sprintf('Message should contain valid %s id', $className));
        }

        $entity = $this->registry->getManagerForClass($className)->find($className, $id);

        if (!$entity) {
            throw new EntityNotFoundException(
                sprintf('Entity %s with identifier %s not found', $className, is_array($id) ? json_encode($id) : $id)
            );
        }

        return $entity;
    }
}
