<?php

namespace Oro\Bundle\WorkflowBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Component\Security\Core\Util\ClassUtils;

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

    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;

    public function __construct(
        WorkflowRegistry $workflowRegistry,
        WorkflowManager $workflowManager,
        ConfigProviderInterface $configProvider
    ) {
        $this->workflowRegistry = $workflowRegistry;
        $this->workflowManager = $workflowManager;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('has_workflows', array($this, 'hasWorkflows')),
            new \Twig_SimpleFunction('get_workflow', array($this, 'getWorkflow')),
            new \Twig_SimpleFunction('get_workflow_item_current_step', array($this, 'getWorkflowItemCurrentStep')),
            new \Twig_SimpleFunction('get_primary_workflow_name', array($this, 'getPrimaryWorkflowName')),
            new \Twig_SimpleFunction('get_primary_workflow_item', array($this, 'getPrimaryWorkflowItem')),
        );
    }

    /**
     * Check for workflow instances
     *
     * @param string $entityClass
     * @return bool
     */
    public function hasWorkflows($entityClass)
    {
        return count($this->workflowRegistry->getWorkflowsByEntityClass($entityClass)) > 0;
    }

    /**
     * Get primary workflow item by entity.
     *
     * @param object $entity
     * @return null|WorkflowItem
     */
    public function getPrimaryWorkflowItem($entity)
    {
        $className = ClassUtils::getRealClass($entity);
        $primaryWorkflowName = $this->getPrimaryWorkflowName($className);
        if ($primaryWorkflowName) {
            $workflowItems = $this->workflowManager->getWorkflowItemsByEntity($entity, $primaryWorkflowName);
            if (count($workflowItems) > 0) {
                $workflowItem = $workflowItems[0];
            } else {
                $workflowItem = $this
                    ->getWorkflow($primaryWorkflowName)
                    ->createWorkflowItem();
            }
            return $workflowItem;
        }

        return null;
    }

    /**
     * Get primary workflow name.
     *
     * @param string $className
     * @return string|null
     */
    public function getPrimaryWorkflowName($className)
    {
        if ($this->configProvider->hasConfig($className)) {
            $entityConfiguration = $this->configProvider->getConfig($className);
            return $entityConfiguration->get('primary');
        }

        return null;
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
        return $workflow->getStepManager()->getStep($workflowItem->getCurrentStepName());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
