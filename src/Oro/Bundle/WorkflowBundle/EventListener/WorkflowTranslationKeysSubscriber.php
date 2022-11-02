<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\WorkflowDefinitionTranslationFieldsIterator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Updates workflow related translations after a workflow is created, updated or deleted.
 */
class WorkflowTranslationKeysSubscriber implements EventSubscriberInterface
{
    private TranslationManager $translationManager;

    public function __construct(TranslationManager $translationManager)
    {
        $this->translationManager = $translationManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkflowEvents::WORKFLOW_AFTER_CREATE => 'ensureTranslationKeys',
            WorkflowEvents::WORKFLOW_AFTER_UPDATE => 'clearTranslationKeys',
            WorkflowEvents::WORKFLOW_AFTER_DELETE => 'deleteTranslationKeys'
        ];
    }

    public function ensureTranslationKeys(WorkflowChangesEvent $changesEvent): void
    {
        $newWorkflowDefinitionFields = new WorkflowDefinitionTranslationFieldsIterator($changesEvent->getDefinition());
        foreach ($newWorkflowDefinitionFields as $translationKey => $value) {
            $this->ensureTranslationKey($translationKey);
        }
        $this->translationManager->flush();
    }

    public function clearTranslationKeys(WorkflowChangesEvent $changesEvent): void
    {
        $originalDefinition = $changesEvent->getOriginalDefinition();

        if ($originalDefinition === null) {
            throw new \LogicException('Previous WorkflowDefinition expected, got null.');
        }

        $updatedDefinitionKeys = new WorkflowDefinitionTranslationFieldsIterator($changesEvent->getDefinition());
        $previousDefinitionKeys = new WorkflowDefinitionTranslationFieldsIterator($originalDefinition);

        $newKeys = [];
        foreach ($updatedDefinitionKeys as $newTranslationKey) {
            if ($newTranslationKey) {
                $this->ensureTranslationKey($newTranslationKey);
                $newKeys[] = $newTranslationKey;
            }
        }

        $oldKeys = array_values(iterator_to_array($previousDefinitionKeys));

        foreach (array_diff($oldKeys, $newKeys) as $translationKeyForRemove) {
            $this->removeTranslationKey($translationKeyForRemove);
        }
        $this->translationManager->flush();
    }

    public function deleteTranslationKeys(WorkflowChangesEvent $workflowChangesEvent): void
    {
        $deletedDefinition = new WorkflowDefinitionTranslationFieldsIterator($workflowChangesEvent->getDefinition());

        foreach ($deletedDefinition as $translationKeyForRemove) {
            $this->removeTranslationKey($translationKeyForRemove);
        }
        $this->translationManager->flush();
    }

    private function ensureTranslationKey(string $key): void
    {
        $this->translationManager->findTranslationKey($key, WorkflowTranslationHelper::TRANSLATION_DOMAIN);
    }

    private function removeTranslationKey(string $key): void
    {
        $this->translationManager->removeTranslationKey($key, WorkflowTranslationHelper::TRANSLATION_DOMAIN);
    }
}
