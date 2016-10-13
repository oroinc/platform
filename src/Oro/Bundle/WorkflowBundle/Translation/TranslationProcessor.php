<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TranslationProcessor implements ConfigurationHandlerInterface, WorkflowDefinitionBuilderExtensionInterface
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
    )
    {
        $this->translationFieldsIterator = $translationFieldsIterator;
        $this->translationHelper = $translationHelper;
    }

    /**
     * @param WorkflowDefinition $actualDefinition
     * @param WorkflowDefinition $previousDefinition
     */
    public function process(WorkflowDefinition $actualDefinition = null, WorkflowDefinition $previousDefinition = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $configuration)
    {
        $configuration = $this->normalizeConfiguration($configuration);

        //TODO BAP-12016 process translated values here

        return $configuration;
    }

    private function normalizeConfiguration(array $configuration)
    {
        if (empty($configuration['label'])) {
            $configuration['label'] = $configuration['name'];
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
        $translationFieldsIterator = $this->translationFieldsIterator->iterateConfigFields(
            $workflowName,
            $configuration
        );

        //fill by reference translatable fields with correct translation keys
        foreach ($translationFieldsIterator as $key => &$value) {
            $value = $key;
        }

        return $configuration;
    }
}
