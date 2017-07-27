<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\WorkflowDefinitionTranslationFieldsIterator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WorkflowTranslationKeysSubscriber implements EventSubscriberInterface
{
    /** @var TranslationManager */
    private $translationManager;

    /**
     * @param TranslationManager $translationManager
     */
    public function __construct(TranslationManager $translationManager)
    {
        $this->translationManager = $translationManager;
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

    /**
     * @param WorkflowChangesEvent $changesEvent
     * @throws \InvalidArgumentException
     */
    public function ensureTranslationKeys(WorkflowChangesEvent $changesEvent)
    {
        $newWorkflowDefinitionFields = new WorkflowDefinitionTranslationFieldsIterator($changesEvent->getDefinition());
        foreach ($newWorkflowDefinitionFields as $translationKey => $value) {
            $this->ensureTranslationKey($translationKey);
        }
        $this->translationManager->flush();
    }

    /**
     * @param WorkflowChangesEvent $changesEvent
     * @throws \LogicException
     */
    public function clearTranslationKeys(WorkflowChangesEvent $changesEvent)
    {
        $originalDefinition = $changesEvent->getOriginalDefinition();

        if ($originalDefinition === null) {
            throw new \LogicException('Previous WorkflowDefinition expected, got null.');
        }

        $updatedDefinitionKeys = new WorkflowDefinitionTranslationFieldsIterator($changesEvent->getDefinition());
        $previousDefinitionKeys = new WorkflowDefinitionTranslationFieldsIterator($originalDefinition);

        $newKeys = [];
        foreach ($updatedDefinitionKeys as $newTranslationKey) {
            $this->ensureTranslationKey($newTranslationKey);
            $newKeys[] = $newTranslationKey;
        }

        $oldKeys = array_values(iterator_to_array($previousDefinitionKeys));

        foreach (array_diff($oldKeys, $newKeys) as $translationKeyForRemove) {
            $this->removeTranslationKey($translationKeyForRemove);
        }
        $this->translationManager->flush();
    }

    /**
     * @param WorkflowChangesEvent $workflowChangesEvent
     * @throws \InvalidArgumentException
     */
    public function deleteTranslationKeys(WorkflowChangesEvent $workflowChangesEvent)
    {
        $deletedDefinition = new WorkflowDefinitionTranslationFieldsIterator($workflowChangesEvent->getDefinition());

        foreach ($deletedDefinition as $translationKeyForRemove) {
            $this->removeTranslationKey($translationKeyForRemove);
        }
        $this->translationManager->flush();
    }

    /**
     * @param string $key
     */
    private function ensureTranslationKey($key)
    {
        $this->translationManager->findTranslationKey($key, WorkflowTranslationHelper::TRANSLATION_DOMAIN);
    }

    /**
     * @param string $key
     */
    private function removeTranslationKey($key)
    {
        $this->translationManager->removeTranslationKey($key, WorkflowTranslationHelper::TRANSLATION_DOMAIN);
    }
}
