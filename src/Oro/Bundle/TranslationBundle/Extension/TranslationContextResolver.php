<?php

namespace Oro\Bundle\TranslationBundle\Extension;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Default context resolver
 */
class TranslationContextResolver implements TranslationContextResolverInterface
{
    /** @var TranslatorInterface */
    protected $translator;

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
