<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;

class TranslationProcessor implements ConfigurationHandlerInterface, WorkflowDefinitionBuilderExtensionInterface
{
    /** @var WorkflowTranslationHelper */
    private $translationHelper;

    /**
     * @param WorkflowTranslationHelper $translationHelper
     */
    public function __construct(WorkflowTranslationHelper $translationHelper)
    {
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

        $translationFieldsIterator = $this->getConfigurationIterator($configuration['name'], $configuration);

        foreach ($translationFieldsIterator as $translationKey => $value) {
            if ($value !== $translationKey) {
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
        $translationFieldsIterator = $this->getConfigurationIterator($workflowName, $configuration);

        //fill translatable fields with it's translation keys
        foreach ($translationFieldsIterator as $translationKey => $value) {
            $translationFieldsIterator->writeCurrent($translationKey);
        }

        return $translationFieldsIterator->getConfiguration();
    }

    /**
     * Converts keys to values of WorkflowDefinition translatable fields. Sets empty string if translation not found.
     * @param WorkflowDefinition $workflowDefinition
     */
    public function translateWorkflowDefinitionFields(WorkflowDefinition $workflowDefinition)
    {
        if (!$workflowDefinition->getName()) {
            return;
        }

        //important to prefetch all translations as getTranslation retrieves them form local instance-level cache
        $workflowName = $workflowDefinition->getName();

        $workflowDefinitionFieldsIterator = new WorkflowDefinitionTranslationFieldsIterator($workflowDefinition);

        foreach ($workflowDefinitionFieldsIterator as $key => $keyValue) {
            $fieldTranslation = $this->translationHelper->findWorkflowTranslation($keyValue, $workflowName);
            $workflowDefinitionFieldsIterator->writeCurrent($fieldTranslation !== $key ? $fieldTranslation : '');
        }
    }

    /**
     * @param string $workflowName
     * @param array $configuration
     * @return WorkflowConfigurationTranslationFieldsIterator
     */
    protected function getConfigurationIterator($workflowName, array $configuration)
    {
        return new WorkflowConfigurationTranslationFieldsIterator($workflowName, $configuration);
    }
}
