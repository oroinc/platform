<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeySource\DynamicTranslationKeySource;
use Oro\Bundle\WorkflowBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\AttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\StepLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionWarningMessageTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

class TranslationProcessor
{
    /** @var TranslationHelper */
    private $translationHelper;

    /** @var TranslationManager */
    private $translationManager;

    /** @var TranslationKeyGenerator */
    private $translationKeyGenerator;

    /**
     * @param TranslationHelper $translationHelper
     * @param TranslationManager $translationManager
     * @param TranslationKeyGenerator $translationKeyGenerator
     */
    public function __construct(
        TranslationHelper $translationHelper,
        TranslationManager $translationManager,
        TranslationKeyGenerator $translationKeyGenerator
    ) {
        $this->translationHelper = $translationHelper;
        $this->translationManager = $translationManager;
        $this->translationKeyGenerator = $translationKeyGenerator;
    }

    /**
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $previousDefinition
     */
    public function process(WorkflowDefinition $definition = null, WorkflowDefinition $previousDefinition = null)
    {
        if ($definition) {
            $this->processUpdate($definition, $previousDefinition);
        }
        if ((!$definition && $previousDefinition) ||
            ($definition && $previousDefinition && $definition->getName() !== $previousDefinition->getName())
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
        $translationKeySource = new TranslationKeySource(
            new WorkflowLabelTemplate(),
            ['workflow_name' => $actualDefinition->getName()]
        );
        $key = $this->translationKeyGenerator->generate($translationKeySource);

        $this->translationHelper->saveTranslation($key, $actualDefinition->getLabel());
        $actualDefinition->setLabel($key);

        $this->updateSteps($actualDefinition, $previousDefinition);
        $this->updateTransitions($actualDefinition, $previousDefinition);
        $this->updateAttributes($actualDefinition, $previousDefinition);
    }

    /**
     * @param WorkflowDefinition $definition
     */
    private function processDelete(WorkflowDefinition $definition)
    {
        $translationKeySource = new TranslationKeySource(
            new WorkflowTemplate(),
            ['workflow_name' => $definition->getName()]
        );
        $keyPrefix = $this->translationKeyGenerator->generate($translationKeySource);
        $this->translationManager->removeTranslationKeysByPrefix($keyPrefix, TranslationHelper::WORKFLOWS_DOMAIN);
    }

    /**
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $previousDefinition
     */
    private function updateSteps(WorkflowDefinition $definition, WorkflowDefinition $previousDefinition = null)
    {
        $template = new StepLabelTemplate();
        $node = WorkflowConfiguration::NODE_STEPS;
        $this->translationHelper->updateNode($template, $node, 'step_name', $definition, $previousDefinition);

        // update WorkflowStep objects
        $configuration = $definition->getConfiguration();
        if (empty($configuration[$node])) {
            return;
        }
        foreach ($configuration[$node] as $name => $stepConfig) {
            if (empty($stepConfig['label'])) {
                continue;
            }
            $step = $definition->getStepByName($name);
            if (!$step) {
                continue;
            }
            $step->setLabel($stepConfig['label']);
        }
    }

    /**
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $previousDefinition
     */
    private function updateTransitions(WorkflowDefinition $definition, WorkflowDefinition $previousDefinition = null)
    {
        $template = new TransitionLabelTemplate();
        $node = WorkflowConfiguration::NODE_TRANSITIONS;
        $this->translationHelper->updateNode($template, $node, 'transition_name', $definition, $previousDefinition);
        $configuration = $definition->getConfiguration();
        if (empty($configuration[$node])) {
            return;
        }

        $translationKeySource = new DynamicTranslationKeySource([
            'workflow_name' => $definition->getName()
        ]);
        $templateMessage = new TransitionWarningMessageTemplate();

        foreach ($configuration[$node] as $name => &$transitionConfig) {
            if (!array_key_exists('message', $transitionConfig)) {
                continue;
            }
            // process transition message
            $messageKey = $this->translationHelper->generateKey(
                $translationKeySource,
                $templateMessage,
                ['transition_name' => $name, 'warning_message' => $transitionConfig['message']]
            );
            $this->translationHelper->saveTranslation($messageKey, $transitionConfig['message']);
        }

        $definition->setConfiguration($configuration);
    }

    /**
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition|null $previousDefinition
     */
    private function updateAttributes(WorkflowDefinition $definition, WorkflowDefinition $previousDefinition = null)
    {
        $template = new AttributeLabelTemplate();
        $node = WorkflowConfiguration::NODE_ATTRIBUTES;
        $this->translationHelper->updateNode($template, $node, 'attribute_name', $definition, $previousDefinition);
    }
}
