<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Basic Implementation of TranslatorAwareInterface.
 */
trait TranslatorAwareTrait
{
    /**
     * @var TranslatorInterface|null
     */
    protected $translator;

    public function setTranslator(?TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }
}
