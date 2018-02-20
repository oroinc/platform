<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowActivationException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowRemoveException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WorkflowDefinitionEntityListener
{
    /** @var ContainerInterface */
    private $container;

    /** @var WorkflowRegistry */
    private $workflowRegistry;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param WorkflowDefinition $definition
     * @throws WorkflowActivationException
     */
    public function prePersist(WorkflowDefinition $definition)
    {
        if ($definition->isActive()) {
            if ($definition->hasExclusiveActiveGroups()) {
                $workflows = $this->getWorkflowRegistry()->getActiveWorkflowsByActiveGroups(
                    $definition->getExclusiveActiveGroups()
                );

                if (count($workflows) !== 0) {
                    throw $this->generateException($definition, $workflows->getValues());
                }
            }

            $this->clearEntitiesCache();
        }
    }

    /**
     * @param WorkflowDefinition $definition
     * @param PreUpdateEventArgs $event
     * @throws WorkflowActivationException
     */
    public function preUpdate(WorkflowDefinition $definition, PreUpdateEventArgs $event)
    {
        $isActivated = $event->hasChangedField('active') && $event->getNewValue('active') === true;
        if ($isActivated) {
            $storedWorkflows = $this->getWorkflowRegistry()->getActiveWorkflowsByActiveGroups(
                $definition->getExclusiveActiveGroups()
            );

            $conflictingWorkflows = $storedWorkflows->filter(function (Workflow $workflow) use ($definition) {
                return $definition->getName() !== $workflow->getName();
            });

            if (!$conflictingWorkflows->isEmpty()) {
                throw $this->generateException($definition, $conflictingWorkflows->getValues());
            }
        }

        if ($isActivated || $event->hasChangedField('relatedEntity')) {
            $this->clearEntitiesCache();
        }
    }

    /**
     * @param WorkflowDefinition $definition
     *
     * @throws WorkflowRemoveException
     */
    public function preRemove(WorkflowDefinition $definition)
    {
        if ($definition->isSystem()) {
            throw new WorkflowRemoveException($definition->getName());
        }

        $this->clearEntitiesCache();
    }

    /**
     * @param WorkflowDefinition $definition
     * @param array|Workflow[] $workflows
     * @return WorkflowActivationException
     */
    private function generateException(WorkflowDefinition $definition, array $workflows)
    {
        $exclusiveActiveGroups = $definition->getExclusiveActiveGroups();

        $conflicts = [];
        foreach ($workflows as $workflow) {
            $groups = $workflow->getDefinition()->getExclusiveActiveGroups();
            foreach ($exclusiveActiveGroups as $group) {
                if (in_array($group, $groups, true)) {
                    $conflicts[] = sprintf(
                        'workflow `%s` by exclusive_active_group `%s`',
                        $workflow->getName(),
                        $group
                    );
                }
            }
        }

        return new WorkflowActivationException(sprintf(
            'Workflow `%s` cannot be activated as it conflicts with %s.',
            $definition->getName(),
            implode(', ', $conflicts)
        ));
    }

    /**
     * @return WorkflowRegistry
     */
    private function getWorkflowRegistry()
    {
        if (null === $this->workflowRegistry) {
            $this->workflowRegistry = $this->container->get('oro_workflow.registry.system');
        }

        return $this->workflowRegistry;
    }

    private function clearEntitiesCache()
    {
        $this->container->get('oro_workflow.cache.entities_with_workflow')->deleteAll();
    }
}
