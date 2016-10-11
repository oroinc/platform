<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Helper\TransitionCronTriggerHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;

class TransitionCronTriggerHandler implements TransitionTriggerHandlerInterface
{
    /** @var WorkflowManager */
    private $workflowManager;

    /** @var TransitionCronTriggerHelper */
    private $helper;

    /**
     * @param WorkflowManager $workflowManager
     * @param TransitionCronTriggerHelper $helper
     */
    public function __construct(WorkflowManager $workflowManager, TransitionCronTriggerHelper $helper)
    {
        $this->workflowManager = $workflowManager;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     */
    public function process(BaseTransitionTrigger $trigger, TransitionTriggerMessage $message)
    {
        if (!$trigger instanceof TransitionCronTrigger) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Trigger should be instance of %s, %s instace given',
                    TransitionCronTrigger::class,
                    ClassUtils::getClass($trigger)
                )
            );
        }

        $workflow = $this->workflowManager->getWorkflow($trigger->getWorkflowDefinition()->getName());
        if (!$workflow) {
            return false;
        }

        $transition = $workflow->getTransitionManager()->getTransition($trigger->getTransitionName());
        if (!$transition) {
            return false;
        }

        if ($transition->isStart()) {
            $this->processStartTransition($trigger, $workflow);
        } else {
            $this->processTransition($trigger, $workflow);
        }

        return true;
    }

    /**
     * @param TransitionCronTrigger $trigger
     * @param Workflow $workflow
     */
    protected function processStartTransition(TransitionCronTrigger $trigger, Workflow $workflow)
    {
        $entities = $this->helper->fetchEntitiesWithoutWorkflowItems($trigger, $workflow);

        $workflowStartArguments = array_map(
            function ($entity) use ($workflow, $trigger) {
                return new WorkflowStartArguments($workflow->getName(), $entity, [], $trigger->getTransitionName());
            },
            $entities
        );

        $this->workflowManager->massStartWorkflow($workflowStartArguments);
    }

    /**
     * @param TransitionCronTrigger $trigger
     * @param Workflow $workflow
     * @throws \Exception
     */
    protected function processTransition(TransitionCronTrigger $trigger, Workflow $workflow)
    {
        $workflowItems = $this->helper->fetchWorkflowItemsForTrigger($trigger, $workflow);

        $data = array_map(
            function (WorkflowItem $workflowItem) use ($trigger) {
                return [
                    'workflowItem' => $workflowItem,
                    'transition' => $trigger->getTransitionName()
                ];
            },
            $workflowItems
        );

        $this->workflowManager->massTransit($data);
    }
}
