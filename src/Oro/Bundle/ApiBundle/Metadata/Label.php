<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * This class represents a translatable string and can be used instead of a string attributes
 * in a configuration and metadata.
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
     * Returns a human-readable representation of this object.
     */
    public function __toString()
    {
        return sprintf('Label: %s', $this->name);
    }
}
