<?php

namespace Oro\Bundle\WorkflowBundle\Twig;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowExtension extends \Twig_Extension
{
    const NAME = 'oro_workflow';

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        //todo fix/remove in BAP-10813 and BAP-10814 usage of entity for single workflow retrieval
        return array(
            new \Twig_SimpleFunction('has_workflow', array($this, 'hasWorkflow')),
            new \Twig_SimpleFunction('has_workflow_start_step', array($this, 'hasWorkflowStartStep')),
            new \Twig_SimpleFunction('has_workflow_item', array($this, 'hasWorkflowItem')),
            new \Twig_SimpleFunction('is_workflow_reset_allowed', array($this, 'isResetAllowed')),
            new \Twig_SimpleFunction('has_workflows', [$this->workflowManager, 'hasApplicableWorkflowsByEntityClass']),
            new \Twig_SimpleFunction('has_workflow_items', [$this->workflowManager, 'hasWorkflowItemsByEntity']),
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

        return $this->workflowManager->hasApplicableWorkflowByEntityClass($entityClass);
    }

    /**
     * Check that entity has workflow item.
     *
     * @param object $entity
     * @return bool
     */
    public function hasWorkflowItem($entity)
    {
        return $this->workflowManager->getWorkflowItemByEntity($entity) !== null;
    }

    /**
     * Check that workflow has start step
     *
     * @param object $entity
     * @return bool
     */
    public function hasWorkflowStartStep($entity)
    {
        $workflow = $this->workflowManager->getApplicableWorkflow($entity);
        if ($workflow) {
            return $workflow->getDefinition()->getStartStep() !== null;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * Check that entity workflow item is equal to the active workflow item.
     *
     * @param object $entity
     * @return bool
     */
    public function isResetAllowed($entity)
    {
        return $this->workflowManager->isResetAllowed($entity);
    }
}
