<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowActivationException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;

class WorkflowDefinitionEntityListener
{
    /** @var WorkflowRegistry */
    private $workflowRegistry;

    /** @var TranslationProcessor */
    private $translationProcessor;

    /** @var array */
    private $scheduled = [];

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param TranslationProcessor $translationProcessor
     */
    public function __construct(WorkflowRegistry $workflowRegistry, TranslationProcessor $translationProcessor)
    {
        $this->workflowRegistry = $workflowRegistry;
        $this->translationProcessor = $translationProcessor;
    }

    /**
     * @param WorkflowDefinition $definition
     * @throws WorkflowActivationException
     */
    public function prePersist(WorkflowDefinition $definition)
    {
        if ($definition->isActive() && $definition->hasExclusiveActiveGroups()) {
            $workflows = $this->workflowRegistry->getActiveWorkflowsByActiveGroups(
                $definition->getExclusiveActiveGroups()
            );

            if (count($workflows) !== 0) {
                throw $this->generateException($definition, $workflows);
            }
        }

        $this->schedule($definition);
    }

    /**
     * @param WorkflowDefinition $definition
     * @param PreUpdateEventArgs $event
     * @throws WorkflowActivationException
     */
    public function preUpdate(WorkflowDefinition $definition, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('active') && $event->getNewValue('active') === true) {
            $storedWorkflows = $this->workflowRegistry->getActiveWorkflowsByActiveGroups(
                $definition->getExclusiveActiveGroups()
            );

            $workflows = array_filter($storedWorkflows, function (Workflow $workflow) use ($definition) {
                return $definition->getName() !== $workflow->getName();
            });

            if (count($workflows) !== 0) {
                throw $this->generateException($definition, $workflows);
            }
        }

        $this->schedule($definition, $event->getEntityChangeSet());
    }

    /**
     * @param WorkflowDefinition $definition
     */
    public function preRemove(WorkflowDefinition $definition)
    {
        $this->schedule($definition, null, true);
    }

    /**
     * @param OnClearEventArgs $args
     */
    public function onClear(OnClearEventArgs $args)
    {
        if ($args->clearsAllEntities() || is_a($args->getEntityClass(), WorkflowDefinition::class, true)) {
            $this->scheduled = [];
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        foreach ($this->scheduled as $data) {
            $this->translationProcessor->process($data['definition'], $data['changeSet'], $data['remove']);
        }

        $this->scheduled = [];
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
     * @param WorkflowDefinition $definition
     * @param array|null $changeSet
     * @param bool $remove
     */
    private function schedule(WorkflowDefinition $definition, array $changeSet = null, $remove = false)
    {
        if ($changeSet !== null) {
            $changeSet = array_intersect_key($changeSet, array_flip(['name', 'label', 'configuration']));

            if (count($changeSet) === 0) {
                return;
            }
        }

        $this->scheduled[$definition->getName()] = [
            'definition' => $definition,
            'changeSet' => $changeSet,
            'remove' => $remove
        ];
    }
}
