<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeySourceInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TranslationProcessor implements ConfigurationHandlerInterface, WorkflowDefinitionBuilderInterface
{
    /** @var WorkflowTranslationFieldsIterator */
    protected $translationFieldsIterator;

    public function __construct()
    {
        $this->translationFieldsIterator = new WorkflowTranslationFieldsIterator();
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

        $generator = new TranslationKeyGenerator();

        foreach ($this->translationFieldsIterator->iterate($configuration) as $source => &$value) {
            /**@var TranslationKeySourceInterface $source */
            $value = $generator->generate($source);
        }
    }
}
