<?php

namespace Oro\Bundle\EmailBundle\Model;

class RecipientEntity
{
    /** @var string */
    protected $class;

    /** @var mixed */
    protected $id;

    /** @var string*/
    protected $label;

    /** @var string|null */
    protected $organization;

    /**
     * @param string $class
     * @param mixed $id
     * @param string $label
     */
    public function __construct($class, $id, $label, $organization = null)
    {
        $this->class = $class;
        $this->id = $id;
        $this->label = $label;
        $this->organization = $organization;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string|null
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
