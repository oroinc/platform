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
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('has_workflows', [$this, 'hasApplicableWorkflows']),
            new TwigFunction('has_workflow_items', [$this, 'hasWorkflowItemsByEntity'])
        ];
    }

    #[\Override]
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

    public function hasApplicableWorkflows(object|string $entity): bool
    {
        return $this->getWorkflowManager()->hasApplicableWorkflows($entity);
    }

    public function hasWorkflowItemsByEntity(object|string $entity): bool
    {
        return $this->getWorkflowManager()->hasWorkflowItemsByEntity($entity);
    }

    public function formatWorkflowVariableValue(Variable $variable): string
    {
        return $this->getWorkflowVariableFormatter()->formatWorkflowVariableValue($variable);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            WorkflowVariableFormatter::class,
            WorkflowManagerRegistry::class
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
