<?php

namespace Oro\Bundle\WorkflowBundle\Twig;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class WorkflowExtension extends \Twig_Extension
{
    const NAME = 'oro_workflow';

    /**
     * @var WorkflowRegistry
     */
    protected $workflowRegistry;

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    public function __construct(
        WorkflowRegistry $workflowRegistry,
        WorkflowManager $workflowManager
    ) {
        $this->workflowRegistry = $workflowRegistry;
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('has_workflow', array($this, 'hasWorkflow')),
            new \Twig_SimpleFunction('has_workflow_item', array($this, 'hasWorkflowItem')),
            new \Twig_SimpleFunction('get_workflow', array($this, 'getWorkflow')),
            new \Twig_SimpleFunction('get_workflow_item_current_step', array($this, 'getWorkflowItemCurrentStep')),
        );
    }

    /**
     * Check for workflow instances
     *
     * @param string $entityClass
     * @return bool
     */
    public function hasWorkflow($entityClass)
    {
        if (!$entityClass) {
            return false;
        }

        return $this->workflowRegistry->getWorkflowByEntityClass($entityClass) !== null;
    }

    /**
     * Check for started workflow instance.
     *
     * @param object $entity
     * @return bool
     */
    public function hasWorkflowItem($entity)
    {
        return $this->workflowManager->getWorkflowItemByEntity($entity) !== null;
    }

    /**
     * Get workflow by workflow identifier
     *
     * @param string|Workflow|WorkflowItem $workflowIdentifier
     * @return Workflow
     */
    public function getWorkflow($workflowIdentifier)
    {
        return $this->workflowManager->getWorkflow($workflowIdentifier);
    }

    /**
     * Get current step by workflow item.
     *
     * @param WorkflowItem $workflowItem
     * @return Step
     */
    public function getWorkflowItemCurrentStep(WorkflowItem $workflowItem)
    {
        $workflow = $this->getWorkflow($workflowItem);

        return $workflow->getStepManager()->getStep($workflowItem->getCurrentStep()->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
