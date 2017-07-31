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

    /** @var string */
    private $domain;

    /** @var bool Always return translated message even if translation does not exist */
    private $translateDirectly = false;

    /**
     * @param string $name
     * @param string|null $domain
     */
    public function __construct($name, $domain = null)
    {
        $this->name = $name;
        $this->domain = $domain;
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
     * @param bool $translateDirectly
     *
     * @return Label
     */
    public function setTranslateDirectly($translateDirectly)
    {
        $this->translateDirectly = $translateDirectly;

        return $this;
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
        $result = $translator->trans($this->name, [], $this->domain);

        if ($this->translateDirectly) {
            return $result;
        }

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
