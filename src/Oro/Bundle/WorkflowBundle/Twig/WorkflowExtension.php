<?php

namespace Oro\Bundle\WorkflowBundle\Twig;

use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationRouteHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowExtension extends \Twig_Extension
{
    const NAME = 'oro_workflow';

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var WorkflowTranslationRouteHelper
     */
    protected $routeHelper;

    /**
     * @param WorkflowManager $workflowManager
     * @param WorkflowTranslationRouteHelper $routeHelper
     */
    public function __construct(WorkflowManager $workflowManager, WorkflowTranslationRouteHelper $routeHelper)
    {
        $this->workflowManager = $workflowManager;
        $this->routeHelper = $routeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('has_workflows', [$this->workflowManager, 'hasApplicableWorkflows']),
            new \Twig_SimpleFunction('has_workflow_items', [$this->workflowManager, 'hasWorkflowItemsByEntity']),
            new \Twig_SimpleFunction('workflow_translation_link', [$this->routeHelper, 'generate'])
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
