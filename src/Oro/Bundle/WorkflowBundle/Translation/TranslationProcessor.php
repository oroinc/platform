<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Translation\KeySource\StepNameSource;
use Oro\Bundle\WorkflowBundle\Translation\KeySource\TransitionNameSource;
use Oro\Bundle\WorkflowBundle\Translation\KeySource\WorkflowNameSource;

class TranslationProcessor
{
    const WORKFLOWS_DOMAIN = 'workflows';

    /** @var WorkflowManager */
    private $workflowManager;

    /** @var Translator */
    private $translator;

    /** @var TranslationManager */
    private $translationManager;

    /** @var TranslationKeyGenerator */
    private $translationKeyGenerator;

    /** @var string */
    private $currentLocale = '';

    /**
     * @param WorkflowManager $workflowManager
     * @param Translator $translator
     * @param TranslationManager $translationManager
     * @param TranslationKeyGenerator $translationKeyGenerator
     */
    public function __construct(
        WorkflowManager $workflowManager,
        Translator $translator,
        TranslationManager $translationManager,
        TranslationKeyGenerator $translationKeyGenerator
    ) {
        $this->workflowManager = $workflowManager;
        $this->translator = $translator;
        $this->translationManager = $translationManager;
        $this->translationKeyGenerator = $translationKeyGenerator;
    }

    /**
     * @param WorkflowDefinition $actualDefinition
     * @param WorkflowDefinition $previousDefinition
     */
    public function process(WorkflowDefinition $actualDefinition = null, WorkflowDefinition $previousDefinition = null)
    {
        if (!$this->currentLocale) {
            $this->currentLocale = $this->translator->getLocale();
        }
        if (null !== $actualDefinition) {
            $this->processUpdate($actualDefinition, $previousDefinition);
        }
        if ((null === $actualDefinition && null !== $previousDefinition) ||
            (null !== $actualDefinition && $actualDefinition->getName() !== $previousDefinition->getName())
        ) {
            $this->processDelete($previousDefinition);
        }
    }

    /**
     * @param WorkflowDefinition $actualDefinition
     * @param WorkflowDefinition $previousDefinition
     */
    private function processUpdate(WorkflowDefinition $actualDefinition, WorkflowDefinition $previousDefinition = null)
    {
        $workflow = $this->workflowManager->getWorkflow($actualDefinition->getName());
        $sourceWorkflowName = new WorkflowNameSource($workflow);
        $key = $this->translationKeyGenerator->generate($sourceWorkflowName);
        $this->translationManager
            ->saveValue($key, $actualDefinition->getLabel(), self::WORKFLOWS_DOMAIN, $this->currentLocale);
        $actualDefinition->setLabel($key);

        $this->updateSteps($workflow, $previousDefinition);
        $this->updateTransitions($workflow, $previousDefinition);
    }

    /**
     * @param WorkflowDefinition $definition
     */
    private function processDelete(WorkflowDefinition $definition)
    {
        // @todo: remove translation keys for this workflow
    }

    /**
     * @param Workflow $workflow
     * @param WorkflowDefinition $previousDefinition
     */
    private function updateSteps(Workflow $workflow, WorkflowDefinition $previousDefinition = null)
    {
        $definition = $workflow->getDefinition();
        $configuration = $definition->getConfiguration();
        if (!isset($configuration[WorkflowConfiguration::NODE_STEPS])) {
            return;
        }
        foreach ($configuration[WorkflowConfiguration::NODE_STEPS] as &$stepConfig) {
            if (!isset($stepConfig['name'], $stepConfig['label'])) {
                continue;
            }
            $step = $definition->getStepByName($stepConfig['name']);
            if (!$step) {
                continue;
            }
            $key = $this->getStepKey($workflow, $stepConfig['name']);
            $this->translationManager
                ->saveValue($key, $stepConfig['label'], self::WORKFLOWS_DOMAIN, $this->currentLocale);
            $step->setLabel($key);
            $stepConfig['label'] = $key;
        }
        if (null !== $previousDefinition) {
            $stepNames = array_column($configuration[WorkflowConfiguration::NODE_STEPS], 'name');
            $stepNamesOld = isset($configurationOld[WorkflowConfiguration::NODE_STEPS])
                ? array_column($configurationOld[WorkflowConfiguration::NODE_STEPS], 'name')
                : [];
            $removedStepNames = array_diff($stepNamesOld, $stepNames);
            foreach ($removedStepNames as $stepName) {
                $key = $this->getStepKey($workflow, $stepName);
                $this->translationManager->removeTranslationKey($key, self::WORKFLOWS_DOMAIN);
            }
            $definition->setConfiguration($configuration);
        }
    }

    /**
     * @param Workflow $workflow
     * @param WorkflowDefinition $previousDefinition
     */
    private function updateTransitions(Workflow $workflow, WorkflowDefinition $previousDefinition = null)
    {
        $definition = $workflow->getDefinition();
        $configuration = $definition->getConfiguration();
        if (!isset($configuration[WorkflowConfiguration::NODE_TRANSITIONS])) {
            return;
        }
        foreach ($configuration[WorkflowConfiguration::NODE_TRANSITIONS] as &$transitionConfig) {
            if (!isset($transitionConfig['name'], $transitionConfig['label'])) {
                continue;
            }
            $key = $this->getTransitionKey($workflow, $transitionConfig['name']);
            $this->translationManager
                ->saveValue($key, $transitionConfig['label'], self::WORKFLOWS_DOMAIN, $this->currentLocale);
            $transitionConfig['label'] = $key;
        }
        if (null !== $previousDefinition) {
            $configurationOld = $previousDefinition->getConfiguration();
            $transitionNames = array_column($configuration[WorkflowConfiguration::NODE_TRANSITIONS], 'name');
            $transitionNamesOld = isset($configurationOld[WorkflowConfiguration::NODE_TRANSITIONS])
                ? array_column($configurationOld[WorkflowConfiguration::NODE_TRANSITIONS], 'name')
                : [];
            $removedTransitionNames = array_diff($transitionNamesOld, $transitionNames);
            foreach ($removedTransitionNames as $transitionName) {
                $key = $this->getTransitionKey($workflow, $transitionName);
                $this->translationManager->removeTranslationKey($key, self::WORKFLOWS_DOMAIN);
            }
            $definition->setConfiguration($configuration);
        }
    }

    /**
     * @param Workflow $workflow
     * @param string $stepName
     *
     * @return string
     */
    private function getStepKey(Workflow $workflow, $stepName)
    {
        $sourceStepName = new StepNameSource($workflow, ['step_name' => $stepName]);

        return $this->translationKeyGenerator->generate($sourceStepName);
    }

    /**
     * @param Workflow $workflow
     * @param string $transitionName
     *
     * @return string
     */
    private function getTransitionKey(Workflow $workflow, $transitionName)
    {
        $sourceTransitionName = new TransitionNameSource($workflow, ['transition_name' => $transitionName]);

        return $this->translationKeyGenerator->generate($sourceTransitionName);
    }
}
