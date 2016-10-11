<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\AttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\StepLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowLabelTemplate;

class KeyTemplateParametersResolver
{
    const TRANSLATION_DOMAIN = 'workflows';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var TranslationKeyTemplateInterface[] */
    protected $templates;

    /**
     * @param TranslatorInterface $translator
     * @param WorkflowManager $workflowManager
     */
    public function __construct(TranslatorInterface $translator, WorkflowManager $workflowManager)
    {
        $this->translator = $translator;
        $this->workflowManager = $workflowManager;

        $this->templates = [
            new WorkflowLabelTemplate(),
            new TransitionLabelTemplate(),
            new StepLabelTemplate(),
            new AttributeLabelTemplate(),
        ];
    }

    /**
     * @param array $parameters
     * @return array
     */
    public function resolveTemplateParameters(array $parameters)
    {
        $resolved = [];

        $this->resolveTransitionByAttribute($parameters);

        foreach ($this->templates as $template) {
            $this->resolveParameter($template, $resolved, $parameters);
        }

        return $resolved;
    }

    /**
     * @param array $parameters
     */
    protected function resolveTransitionByAttribute(array &$parameters)
    {
        if (!array_key_exists('attribute_name', $parameters)) {
            return;
        }

        $workflow = $this->workflowManager->getWorkflow($parameters['workflow_name']);

        foreach ($workflow->getTransitionManager()->getTransitions() as $transition) {
            $formOptions = $transition->getFormOptions();
            if (array_key_exists($parameters['attribute_name'], $formOptions['attribute_fields'])) {
                $parameters['transition_name'] = $transition->getName();
                break;
            }
        }
    }

    /**
     * @param TranslationKeyTemplateInterface $template
     * @param array $resolved
     * @param array $parameters
     */
    protected function resolveParameter(TranslationKeyTemplateInterface $template, array &$resolved, array $parameters)
    {
        if (!empty(array_diff($template->getRequiredKeys(), array_keys($parameters)))) {
            return;
        }

        $templateKey = $template->getTemplate();

        foreach ($parameters as $key => $value) {
            $templateKey = str_replace($template->getKeyTemplate($key), $value, $templateKey);
        }

        $resolved[$template->getKeyTemplate($template->getName())] = $this->translator->trans(
            $templateKey,
            [],
            self::TRANSLATION_DOMAIN
        );
    }
}
