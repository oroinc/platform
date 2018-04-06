<?php

namespace Oro\Bundle\WorkflowBundle\Twig;

use Oro\Bundle\WorkflowBundle\Formatter\WorkflowVariableFormatter;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WorkflowExtension extends \Twig_Extension
{
    const NAME = 'oro_workflow';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return WorkflowManager
     */
    protected function getWorkflowManager()
    {
        return $this->container->get('oro_workflow.registry.workflow_manager')->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('has_workflows', [$this, 'hasApplicableWorkflows']),
            new \Twig_SimpleFunction('has_workflow_items', [$this, 'hasWorkflowItemsByEntity'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_format_workflow_variable_value',
                [$this, 'formatWorkflowVariableValue'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param object|string $entity
     *
     * @return bool
     */
    public function hasApplicableWorkflows($entity)
    {
        return $this->getWorkflowManager()->hasApplicableWorkflows($entity);
    }

    /**
     * @param object|string $entity
     *
     * @return bool
     */
    public function hasWorkflowItemsByEntity($entity)
    {
        return $this->getWorkflowManager()->hasWorkflowItemsByEntity($entity);
    }

    /**
     * @param Variable $variable
     *
     * @return string
     */
    public function formatWorkflowVariableValue(Variable $variable)
    {
        return $this->getWorkflowVariableFormatter()->formatWorkflowVariableValue($variable);
    }

    /**
     * @return WorkflowVariableFormatter
     */
    protected function getWorkflowVariableFormatter()
    {
        return $this->container->get('oro_workflow.formatter.workflow_variable');
    }
}
