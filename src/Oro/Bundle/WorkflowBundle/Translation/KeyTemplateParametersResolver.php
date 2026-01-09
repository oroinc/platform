<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Resolves translation key template parameters for workflow components.
 *
 * This resolver matches provided parameters against known translation key templates
 * for workflows, transitions, steps, and attributes, enabling proper translation key generation.
 */
class KeyTemplateParametersResolver
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var TranslationKeyTemplateInterface[] */
    protected $templates;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        $this->templates = [
            new KeyTemplate\WorkflowLabelTemplate(),
            new KeyTemplate\TransitionLabelTemplate(),
            new KeyTemplate\StepLabelTemplate(),
            new KeyTemplate\TransitionAttributeLabelTemplate(),
            new KeyTemplate\WorkflowAttributeLabelTemplate(),
        ];
    }

    /**
     * @param array $parameters
     * @return array
     */
    public function resolveTemplateParameters(array $parameters)
    {
        $resolved = [];

        foreach ($this->templates as $template) {
            $this->resolveParameter($template, $resolved, $parameters);
        }

        return $resolved;
    }

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
            WorkflowTranslationHelper::TRANSLATION_DOMAIN
        );
    }
}
