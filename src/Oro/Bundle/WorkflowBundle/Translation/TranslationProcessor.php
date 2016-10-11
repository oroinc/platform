<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeySourceInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TranslationProcessor implements ConfigurationHandlerInterface, WorkflowDefinitionBuilderInterface
{
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
     * {@inheritdoc}
     */
    public function build(WorkflowDefinition $definition, array &$configuration)
    {

        $configuration['name'] = $definition->getName();
        $fieldsIterator = new WorkflowTranslationFieldsGenerator();

        $generator = new TranslationKeyGenerator();


        foreach ($fieldsIterator->iterate($configuration) as $source => &$value) {
            /**@var TranslationKeySourceInterface $source */
            $value = $generator->generate($source);
        }

        return;
        //$workflowName = $configuration['name'];
        //$configuration['label'] = $this->workflowLabelKey($workflowName);
        //foreach ($configuration as $nodeName => &$nodeValue) {
        //    if ($nodeName === 'transitions') {
        //        foreach ($nodeValue as $transitionName => &$transitionConfig) {
        //            $transitionConfig['label'] = $this->transitionLabelKey(
        //                $workflowName, $transitionName
        //            );
        //
        //            $transitionConfig['message'] = $this->transitionWarningMessageKey(
        //                $workflowName, $transitionName
        //            );
        //        }
        //        unset($transitionConfig);
        //    }
        //
        //    if ($nodeName === 'steps') {
        //        foreach ($nodeValue as $stepName => &$stepConfig) {
        //            $stepConfig['label'] = $this->stepLabelKey($workflowName, $stepName);
        //        }
        //        unset($stepConfig);
        //    }
        //
        //    if ($nodeName === 'attributes') {
        //        foreach ($nodeValue as $attributeName => &$attributeConfig) {
        //            $attributeConfig['label'] = $this->attributeLabelKey($workflowName, $attributeName);
        //        }
        //    }
        //}
    }

    /**
     * @param string $workflowName
     * @return string
     */
    private function workflowLabelKey($workflowName)
    {
    }

    /**
     * @param string $workflowName
     * @param string $transitionName
     * @return string
     */
    private function transitionLabelKey($workflowName, $transitionName)
    {
    }

    /**
     * @param string $workflowName
     * @param string $transitionName
     * @return string
     */
    private function transitionWarningMessageKey($workflowName, $transitionName)
    {
    }

    /**
     * @param string $workflowName
     * @param string $stepName
     * @return string
     */
    private function stepLabelKey($workflowName, $stepName)
    {
    }

    /**
     * @param $workflowName
     * @param $attributeName
     * @return string
     */
    private function attributeLabelKey($workflowName, $attributeName)
    {
    }
}
