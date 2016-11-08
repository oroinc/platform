<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TranslationBundle\Extension\TranslationContextResolverInterface;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplateParametersResolver;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class TranslationContextResolver implements TranslationContextResolverInterface
{
    const TRANSLATION_TEMPLATE = 'oro.workflow.translation.context.{{ template }}';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var KeyTemplateParametersResolver */
    protected $resolver;

    /** @var TranslationKeyTemplateInterface[] */
    protected $templates;

    /**
     * @param TranslatorInterface $translator
     * @param KeyTemplateParametersResolver $resolver
     */
    public function __construct(TranslatorInterface $translator, KeyTemplateParametersResolver $resolver)
    {
        $this->translator = $translator;
        $this->resolver = $resolver;

        $this->templates = [
            new KeyTemplate\WorkflowLabelTemplate(),
            new KeyTemplate\TransitionLabelTemplate(),
            new KeyTemplate\TransitionWarningMessageTemplate(),
            new KeyTemplate\StepLabelTemplate(),
            new KeyTemplate\TransitionAttributeLabelTemplate(),
            new KeyTemplate\WorkflowAttributeLabelTemplate(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($id)
    {
        if (0 !== strpos($id, KeyTemplate\WorkflowTemplate::KEY_PREFIX)) {
            return null;
        }

        if (null === ($resolvedTemplate = $this->resolveSourceByKey($id))) {
            return null;
        }

        $resolvedId = str_replace('{{ template }}', $resolvedTemplate[0]->getName(), self::TRANSLATION_TEMPLATE);
        $resolvedParameters = $this->resolver->resolveTemplateParameters($resolvedTemplate[1]);

        return $this->translator->trans($resolvedId, $resolvedParameters);
    }

    /**
     * @param string $id
     * @return array|null
     */
    protected function resolveSourceByKey($id)
    {
        $sourceKeyParts = explode('.', $id);

        foreach ($this->templates as $sourceTemplate) {
            $keyTemplates = $sourceTemplate->getKeyTemplates();

            $templateParts = explode('.', $sourceTemplate->getTemplate());

            if (count($sourceKeyParts) !== count($templateParts)) {
                continue;
            }

            $parameters = [];

            foreach ($sourceKeyParts as $key => $part) {
                if (false !== ($attribute = array_search($templateParts[$key], $keyTemplates, true))) {
                    $templateParts[$key] = $part;
                    $parameters[$attribute] = $part;
                }
            }

            if ($sourceKeyParts === $templateParts) {
                return [$sourceTemplate, $parameters];
            }
        }

        return null;
    }
}
