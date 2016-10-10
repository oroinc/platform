<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
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
     * @param WorkflowDefinition|null $definition
     * @param WorkflowDefinition|null $previousDefinition
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
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition|null $previousDefinition
     */
    private function processUpdate(WorkflowDefinition $definition, WorkflowDefinition $previousDefinition = null)
    {
        $translationKeySource = new TranslationKeySource(
            new WorkflowLabelTemplate(),
            ['workflow_name' => $definition->getName()]
        );
        $key = $this->translationKeyGenerator->generate($translationKeySource);
        if ($definition->getLabel()) {
            $this->translationHelper->saveTranslation($key, $definition->getLabel());
        } else {
            $this->translationManager->findTranslationKey($key, TranslationHelper::WORKFLOWS_DOMAIN);
        }
        $definition->setLabel($key);

        $this->updateSteps($definition, $previousDefinition);
        $this->updateTransitions($definition, $previousDefinition);
        $this->updateAttributes($definition, $previousDefinition);
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
        $this->translationHelper->updateNodeKeys($template, $node, 'step_name', 'label', $definition);
        $this->translationHelper->cleanupNodeKeys($template, $node, 'step_name', $definition, $previousDefinition);

        // update WorkflowStep objects
        $configuration = $definition->getConfiguration();
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
     * @param WorkflowDefinition|null $previousDefinition
     */
    private function updateTransitions(WorkflowDefinition $definition, WorkflowDefinition $previousDefinition = null)
    {
        $template = new TransitionLabelTemplate();
        $node = WorkflowConfiguration::NODE_TRANSITIONS;
        $this->translationHelper->updateNodeKeys($template, $node, 'transition_name', 'label', $definition);
        $this->translationHelper
            ->cleanupNodeKeys($template, $node, 'transition_name', $definition, $previousDefinition);

        $templateMessage = new TransitionWarningMessageTemplate();
        $this->translationHelper->updateNodeKeys($templateMessage, $node, 'transition_name', 'message', $definition);
        $this->translationHelper
            ->cleanupNodeKeys($templateMessage, $node, 'transition_name', $definition, $previousDefinition);
    }

    /**
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition|null $previousDefinition
     */
    private function updateAttributes(WorkflowDefinition $definition, WorkflowDefinition $previousDefinition = null)
    {
        $template = new AttributeLabelTemplate();
        $node = WorkflowConfiguration::NODE_ATTRIBUTES;
        $this->translationHelper->updateNodeKeys($template, $node, 'attribute_name', 'label', $definition);
        $this->translationHelper->cleanupNodeKeys($template, $node, 'attribute_name', $definition, $previousDefinition);
    }
}
