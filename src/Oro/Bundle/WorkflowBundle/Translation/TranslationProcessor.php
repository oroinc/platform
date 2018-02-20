<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

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
        $parentKeyPrefix = $this->getParentKeyPrefix($configuration['name']);

        foreach ($translationFieldsIterator as $translationKey => $value) {
            if ($value !== $translationKey) {
                //In case when workflow is a clone, all parent keys used as values should be translated
                //to proper the same values at cloned
                if ($parentKeyPrefix && (stripos($value, $parentKeyPrefix) === 0)) {
                    $value = $this->translationHelper->findValue($value);
                }

                $this->translationHelper->saveTranslation($translationKey, $value);
            } elseif ($this->isNotRequiredField($translationKey)) {
                $this->translationHelper->saveTranslationAsSystem($translationKey, '');
            }
        }

        $this->translationHelper->flushTranslations();

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
     * Converts keys to values of WorkflowDefinition translatable fields.
     * Sets empty string if translation not found or if passed 'true' to $useKeyAsTranslation
     * then keys instead of empty string.
     *
     * @param WorkflowDefinition $workflowDefinition
     * @param bool $useKeyAsTranslation
     */
    public function translateWorkflowDefinitionFields(
        WorkflowDefinition $workflowDefinition,
        $useKeyAsTranslation = false
    ) {
        if (!$workflowDefinition->getName()) {
            return;
        }

        //important to prefetch all translations as getTranslation retrieves them form local instance-level cache
        $workflowName = $workflowDefinition->getName();

        $workflowDefinitionFieldsIterator = new WorkflowDefinitionTranslationFieldsIterator($workflowDefinition);

        foreach ($workflowDefinitionFieldsIterator as $key => $keyValue) {
            $fieldTranslation = $this->translationHelper->findWorkflowTranslation($keyValue, $workflowName);
            if ($fieldTranslation === $key) {
                //Skip not required fields because they use it's own logic
                if ($this->isNotRequiredField($fieldTranslation)) {
                    $fieldTranslation = '';
                } else {
                    $fieldTranslation = $useKeyAsTranslation ? $key : '';
                }
            }

            $workflowDefinitionFieldsIterator->writeCurrent($fieldTranslation);
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

    /**
     * @param string $name
     *
     * @return null|string
     */
    private function getParentKeyPrefix($name)
    {
        $matches = [];
        $parentKeyPrefix = null;
        $isCloned = preg_match('/(.+)_clone_[a-z0-9]+$/', $name, $matches);
        if ($isCloned && !empty($matches[1])) {
            $parentKeyPrefix = $this->getKeyPrefix($matches[1]);
        }

        return $parentKeyPrefix;
    }

    /**
     * @param $workflowName
     *
     * @return string
     */
    private function getKeyPrefix($workflowName)
    {
        $generator = new TranslationKeyGenerator();

        return $generator->generate(
            new TranslationKeySource(new WorkflowTemplate(), ['workflow_name' => $workflowName])
        );
    }

    /**
     * @param string $field
     * @return int
     */
    protected function isNotRequiredField($field)
    {
        return preg_match('/^oro\.workflow\..+\.transition\..+\.(warning_message|button_label|button_title)$/', $field);
    }
}
