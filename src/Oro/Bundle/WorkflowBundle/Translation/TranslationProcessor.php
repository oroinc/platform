<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;

class TranslationProcessor implements
    ConfigurationHandlerInterface,
    WorkflowDefinitionBuilderExtensionInterface,
    EventSubscriberInterface
{
    /** @var TranslationHelper */
    private $translationHelper;

    /** @var WorkflowTranslationFieldsIterator */
    protected $translationFieldsIterator;

    /**
     * @param WorkflowTranslationFieldsIterator $translationFieldsIterator
     * @param TranslationHelper $translationHelper
     */
    public function __construct(
        WorkflowTranslationFieldsIterator $translationFieldsIterator,
        TranslationHelper $translationHelper
    ) {
        $this->translationFieldsIterator = $translationFieldsIterator;
        $this->translationHelper = $translationHelper;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function handle(array $configuration)
    {
        if (empty($configuration['name'])) {
            throw new \InvalidArgumentException('Workflow configuration for handler must contain valid `name` node.');
        }

        $workflowName = $configuration['name'];

        /** @noinspection ReferenceMismatchInspection */
        $translationFieldsIterator = $this->translationFieldsIterator->iterateConfigTranslationFields(
            $workflowName,
            $configuration
        );

        foreach ($translationFieldsIterator as $key => &$value) {
            if ($key !== $value && (string)$value !== '') {
                $this->translationHelper->saveTranslation($key, $value);
            }
        }

        return $configuration;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function prepare($workflowName, array $configuration)
    {
        /** @noinspection ReferenceMismatchInspection */
        $translationFieldsIterator = $this->translationFieldsIterator->iterateConfigTranslationFields(
            $workflowName,
            $configuration
        );

        //fill by reference translatable fields with correct translation keys
        foreach ($translationFieldsIterator as $key => &$value) {
            $value = $key;
        }

        return $configuration;
    }

    /**
     * @param WorkflowChangesEvent $changesEvent
     * @throws \InvalidArgumentException
     */
    public function ensureTranslationKeys(WorkflowChangesEvent $changesEvent)
    {
        $keys = $this->translationFieldsIterator->iterateWorkflowDefinition($changesEvent->getDefinition());
        foreach ($keys as $translationKey) {
            $this->translationHelper->ensureTranslationKey($translationKey);
        }
    }

    /**
     * @param WorkflowChangesEvent $changesEvent
     */
    public function clearTranslationKeys(WorkflowChangesEvent $changesEvent)
    {

        $previousDefinition = $changesEvent->getPrevious();

        if ($previousDefinition === null) {
            throw new \LogicException('Previous WorkflowDefinition expected. But got null.');
        }

        $updatedDefinitionKeys = $this->translationFieldsIterator->iterateWorkflowDefinition(
            $changesEvent->getDefinition()
        );

        $previousDefinitionKeys = $this->translationFieldsIterator->iterateWorkflowDefinition($previousDefinition);

        $newKeys = [];
        foreach ($updatedDefinitionKeys as $newTranslationKey) {
            $this->translationHelper->ensureTranslationKey($newTranslationKey);
            $newKeys[] = $newTranslationKey;
        }

        $oldKeys = array_values(iterator_to_array($previousDefinitionKeys));

        foreach (array_diff($oldKeys, $newKeys) as $translationKeyForRemove) {
            $this->translationHelper->removeTranslationKey($translationKeyForRemove);
        }
    }

    /**
     * @param WorkflowChangesEvent $workflowChangesEvent
     * @throws \InvalidArgumentException
     */
    public function deleteTranslationKeys(WorkflowChangesEvent $workflowChangesEvent)
    {
        $keysForRemoval = $this->translationFieldsIterator->iterateWorkflowDefinition(
            $workflowChangesEvent->getDefinition()
        );

        foreach ($keysForRemoval as $translationKey) {
            $this->translationHelper->removeTranslationKey($translationKey);
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
