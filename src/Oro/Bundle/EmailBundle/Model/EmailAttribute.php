<?php

namespace Oro\Bundle\EmailBundle\Model;

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
