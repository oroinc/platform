<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\WorkflowDefinitionTranslationFieldsIterator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WorkflowTranslationKeysSubscriber implements EventSubscriberInterface
{
    /** @var WorkflowTranslationHelper */
    private $workflowTranslationHelper;

    /**
     * @param WorkflowTranslationHelper $workflowTranslationHelper
     */
    public function __construct(WorkflowTranslationHelper $workflowTranslationHelper)
    {
        $this->workflowTranslationHelper = $workflowTranslationHelper;
    }

    /**
     * @param WorkflowChangesEvent $changesEvent
     * @throws \InvalidArgumentException
     */
    public function ensureTranslationKeys(WorkflowChangesEvent $changesEvent)
    {
        $newWorkflowDefinitionFields = new WorkflowDefinitionTranslationFieldsIterator($changesEvent->getDefinition());
        foreach ($newWorkflowDefinitionFields as $translationKey => $value) {
            $this->workflowTranslationHelper->ensureTranslationKey($translationKey);
        }
    }

    /**
     * @param WorkflowChangesEvent $changesEvent
     * @throws \LogicException
     */
    public function clearTranslationKeys(WorkflowChangesEvent $changesEvent)
    {
        $previousDefinition = $changesEvent->getPrevious();

        if ($previousDefinition === null) {
            throw new \LogicException('Previous WorkflowDefinition expected. But got null.');
        }

        $updatedDefinitionKeys = new WorkflowDefinitionTranslationFieldsIterator($changesEvent->getDefinition());

        $previousDefinitionKeys = new WorkflowDefinitionTranslationFieldsIterator($previousDefinition);

        $newKeys = [];
        foreach ($updatedDefinitionKeys as $newTranslationKey) {
            $this->workflowTranslationHelper->ensureTranslationKey($newTranslationKey);
            $newKeys[] = $newTranslationKey;
        }

        $oldKeys = array_values(iterator_to_array($previousDefinitionKeys));

        foreach (array_diff($oldKeys, $newKeys) as $translationKeyForRemove) {
            $this->workflowTranslationHelper->removeTranslationKey($translationKeyForRemove);
        }
    }

    /**
     * @param WorkflowChangesEvent $workflowChangesEvent
     * @throws \InvalidArgumentException
     */
    public function deleteTranslationKeys(WorkflowChangesEvent $workflowChangesEvent)
    {
        $deletedDefinition = new WorkflowDefinitionTranslationFieldsIterator($workflowChangesEvent->getDefinition());

        foreach ($deletedDefinition as $translationKeyForRemove) {
            $this->workflowTranslationHelper->removeTranslationKey($translationKeyForRemove);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            WorkflowEvents::WORKFLOW_AFTER_CREATE => 'ensureTranslationKeys',
            WorkflowEvents::WORKFLOW_AFTER_UPDATE => 'clearTranslationKeys',
            WorkflowEvents::WORKFLOW_AFTER_DELETE => 'deleteTranslationKeys'
        ];
    }
}
