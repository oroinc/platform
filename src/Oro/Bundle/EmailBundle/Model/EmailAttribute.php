<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Represents an email attribute for template variable rendering.
 *
 * Encapsulates metadata about an email attribute including its name and whether it represents
 * an association, used for template variable discovery and rendering.
 */
class EmailAttribute
{
    /** @var string */
    protected $name;

    /** @var bool */
    protected $association;

    /**
     * @param string $name
     * @param bool $association
     */
    public function __construct($name, $association = false)
    {
        $this->name = $name;
        $this->association = $association;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isAssociation()
    {
        return $this->association;
    }
}
