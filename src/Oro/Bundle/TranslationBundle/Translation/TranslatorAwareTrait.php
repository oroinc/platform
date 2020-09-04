<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Basic Implementation of TranslatorAwareInterface.
 */
trait TranslatorAwareTrait
{
    /**
     * @var TranslatorInterface|null
     */
    protected $translator;

    /**
     * @param TranslatorInterface|null $translator
     */
    public function setTranslator(?TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }
}
