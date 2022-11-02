<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowActivationException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowRemoveException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Invalidate workflow cache on change workflow definition
 */
class WorkflowDefinitionEntityListener
{
    private WorkflowRegistry $workflowRegistry;
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache, WorkflowRegistry $workflowRegistry)
    {
        $this->cache = $cache;
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * @throws WorkflowActivationException
     */
    public function prePersist(WorkflowDefinition $definition): void
    {
        if ($definition->isActive()) {
            if ($definition->hasExclusiveActiveGroups()) {
                $workflows = $this->workflowRegistry->getActiveWorkflowsByActiveGroups(
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
     * @throws WorkflowActivationException
     */
    public function preUpdate(WorkflowDefinition $definition, PreUpdateEventArgs $event): void
    {
        $isActivated = $event->hasChangedField('active') && $event->getNewValue('active') === true;
        if ($isActivated) {
            $storedWorkflows = $this->workflowRegistry->getActiveWorkflowsByActiveGroups(
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
     * @throws WorkflowRemoveException
     */
    public function preRemove(WorkflowDefinition $definition): void
    {
        if ($definition->isSystem()) {
            throw new WorkflowRemoveException($definition->getName());
        }

        $this->clearEntitiesCache();
    }

    private function generateException(WorkflowDefinition $definition, array $workflows): WorkflowActivationException
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

    private function clearEntitiesCache(): void
    {
        $this->cache->clear();
    }
}
