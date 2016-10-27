<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class KeyTemplateParametersResolver
{
    const TRANSLATION_DOMAIN = 'workflows';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var TranslationKeyTemplateInterface[] */
    protected $templates;

    /**
     * @param TranslatorInterface $translator
     */
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
