<?php

namespace Oro\Bundle\LocaleBundle\DataFixtures;

/**
 * Denotes classes that can accept formattingCode and language options.
 */
interface LocalizationOptionsAwareInterface
{
    public function setFormattingCode(string $formattingCode): self;

    public function setLanguage(string $language): self;
}
