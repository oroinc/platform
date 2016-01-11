<?php

namespace Oro\Bundle\ApiBundle\Model;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * This class represents a translatable string and can be used instead of a string attributes
 * in a configuration.
 */
class Label
{
    /** @var string */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the translation key.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the translation key.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Translates this label.
     *
     * @param TranslatorInterface $translator
     *
     * @return string
     */
    public function trans(TranslatorInterface $translator)
    {
        $result = $translator->trans($this->name);

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
