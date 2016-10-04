<?php

namespace Oro\Bundle\TranslationBundle\Extension;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Default context resolver
 */
class TranslationContextResolver implements TranslationContextResolverInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($id)
    {
        return $this->translator->trans('oro.translation.context.ui_label');
    }
}
