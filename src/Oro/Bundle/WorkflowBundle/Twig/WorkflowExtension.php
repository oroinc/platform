<?php

namespace Oro\Bundle\WorkflowBundle\Twig;

use Oro\Bundle\WorkflowBundle\Formatter\WorkflowVariableFormatter;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides Twig functions to determine if an entity has associated workflows:
 *   - has_workflows
 *   - has_workflow_items
 *
 * Provides Twig filter to format a workflow variable value in workflow management:
 *   - oro_format_workflow_variable_value
 */
class WorkflowExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('has_workflows', [$this, 'hasApplicableWorkflows']),
            new TwigFunction('has_workflow_items', [$this, 'hasWorkflowItemsByEntity'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'oro_format_workflow_variable_value',
                [$this, 'formatWorkflowVariableValue'],
                ['is_safe' => ['html']]
            ),
        ];
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
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            WorkflowVariableFormatter::class,
            WorkflowManagerRegistry::class,
        ];
    }

    private function getWorkflowVariableFormatter(): WorkflowVariableFormatter
    {
        return $this->container->get(WorkflowVariableFormatter::class);
    }

    private function getWorkflowManager(): WorkflowManager
    {
        return $this->container->get(WorkflowManagerRegistry::class)->getManager();
    }
}
