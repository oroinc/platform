<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Describes a translator-aware instance.
 */
interface TranslatorAwareInterface
{
    /**
     * Sets a translator instance on the object.
     */
    public function setTranslator(TranslatorInterface $translator): void;
}
