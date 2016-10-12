<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TranslationProcessor implements ConfigurationHandlerInterface, WorkflowDefinitionBuilderInterface
{
    /** @var WorkflowTranslationFieldsIterator */
    protected $translationFieldsIterator;

    /**
     * @param WorkflowTranslationFieldsIterator $translationFieldsIterator
     */
    public function __construct(WorkflowTranslationFieldsIterator $translationFieldsIterator)
    {
        $this->translationFieldsIterator = $translationFieldsIterator;
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
     * Define translation keys for translated fields in workflow configuration before it builds.
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function build(WorkflowDefinition $definition, array &$configuration)
    {
        //ensure workflow name defined in configuration
        $configuration['name'] = $definition->getName();

        //fill by reference translatable fields with correct translation keys
        foreach ($this->translationFieldsIterator->iterateWorkflowConfiguration($configuration) as $key => &$value) {
            $value = $key;
        }
    }
}
