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

    /**
     * @param WorkflowManager $workflowManager
     */
    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('has_workflows', [$this->workflowManager, 'hasApplicableWorkflowsByEntityClass']),
            new \Twig_SimpleFunction('has_workflow_items', [$this->workflowManager, 'hasWorkflowItemsByEntity'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
