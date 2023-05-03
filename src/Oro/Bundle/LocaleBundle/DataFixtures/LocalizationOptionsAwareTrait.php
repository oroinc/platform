<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\DataFixtures;

/**
 * Boilerplate implementation of LocalizationOptionsAwareInterface
 * @see LocalizationOptionsAwareInterface
 */
trait LocalizationOptionsAwareTrait
{
    protected string $formattingCode;
    protected string $language;

    public function setFormattingCode(string $formattingCode): self
    {
        $this->formattingCode = $formattingCode;
        return $this;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }
}
