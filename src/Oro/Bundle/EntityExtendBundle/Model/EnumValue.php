<?php

namespace Oro\Bundle\EntityExtendBundle\Model;

class EnumValue
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var bool
     */
    protected $is_default;

    /**
     * @var int
     */
    protected $priority;

    /**
     * @param array $data
     * @return EnumValue
     */
    public function fromArray(array $data)
    {
        if (isset($data['id'])) {
            $this->setLabel($data['id']);
        }

        if (isset($data['label'])) {
            $this->setLabel($data['label']);
        }

        if (isset($data['is_default'])) {
            $this->setIsDefault($data['is_default']);
        }

        if (isset($data['priority'])) {
            $this->setPriority($data['priority']);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = [];

        if ($this->getId()) {
            $data['id'] = $this->getId();
        }
        if ($this->getLabel()) {
            $data['label'] = $this->getLabel();
        }
        if ($this->getIsDefault()) {
            $data['is_default'] = $this->getIsDefault();
        }
        if ($this->getPriority()) {
            $data['priority'] = $this->getPriority();
        }

        return $data;
    }

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return EnumValue
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return EnumValue
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     *
     * @return EnumValue
     */
    public function setIsDefault($isDefault)
    {
        $this->is_default = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     *
     * @return EnumValue
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
