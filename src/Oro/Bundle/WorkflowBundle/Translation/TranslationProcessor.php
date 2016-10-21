<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;

class TranslationProcessor implements
    ConfigurationHandlerInterface,
    WorkflowDefinitionBuilderExtensionInterface,
    EventSubscriberInterface
{
    /** @var WorkflowTranslationHelper */
    private $translationHelper;

    /**
     * @param WorkflowTranslationHelper $translationHelper
     */
    public function __construct(
        WorkflowTranslationHelper $translationHelper
    ) {
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

        $translationFieldsIterator = new WorkflowConfigurationTranslationFieldsIterator($workflowName, $configuration);

        foreach ($translationFieldsIterator as $translationKey => $value) {
            if ($translationKey !== $value && (string)$value !== '') {
                $this->translationHelper->saveTranslation($translationKey, $value);
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
        $translationFieldsIterator = new WorkflowConfigurationTranslationFieldsIterator($workflowName, $configuration);

        //fill translatable fields with it's translation keys
        foreach ($translationFieldsIterator as $translationKey => $value) {
            $translationFieldsIterator->writeCurrent($translationKey);
        }

        return $translationFieldsIterator->getConfiguration();
    }

    /**
     * @param WorkflowChangesEvent $changesEvent
     * @throws \InvalidArgumentException
     */
    public function ensureTranslationKeys(WorkflowChangesEvent $changesEvent)
    {
        $newWorkflowDefinitionFields = new WorkflowDefinitionTranslationFieldsIterator($changesEvent->getDefinition());
        foreach ($newWorkflowDefinitionFields as $translationKey => $value) {
            $this->translationHelper->ensureTranslationKey($translationKey);
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
        $deletedDefinition = new WorkflowDefinitionTranslationFieldsIterator($workflowChangesEvent->getDefinition());

        foreach ($deletedDefinition as $translationKeyForRemove) {
            $this->translationHelper->removeTranslationKey($translationKeyForRemove);
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
