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

    /**
     * @return string|null
     */
    public function getAdditionalInfo()
    {
        $additionalInfo = [];
        if ($this->organization) {
            $additionalInfo[] = $this->organization;
        }

        if ($typeLabel = $this->getTypeLabel()) {
            $additionalInfo[] = $typeLabel;
        }

        if (!$additionalInfo) {
            return null;
        }

        return sprintf('(%s)', implode(' ', $additionalInfo));
    }

    /**
     * @return string|null
     */
    protected function getTypeLabel()
    {
        if (false === $start = strrpos($this->label, '(')) {
            return null;
        }

        return substr($this->label, $start + 1, -1);
    }
}
