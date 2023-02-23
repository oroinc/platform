<?php

namespace Oro\Bundle\ApiBundle\Model;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This class represents a translatable string and can be used instead of a string attributes
 * in a configuration.
 */
class Label
{
    private string $name;
    private ?string $domain;

    public function __construct(string $name, ?string $domain = null)
    {
        $this->name = $name;
        $this->domain = $domain;
    }

    /**
     * Gets the translation key.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the translation key.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Translates this label.
     */
    public function trans(TranslatorInterface $translator): string
    {
        $result = $translator->trans($this->name, [], $this->domain);

        return $result !== $this->name
            ? $result
            : '';
    }

    /**
     * Returns a human-readable representation of this object.
     */
    public function __toString()
    {
        return sprintf('Label: %s', $this->name);
    }
}
