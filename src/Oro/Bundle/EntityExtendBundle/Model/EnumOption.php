<?php

namespace Oro\Bundle\EntityExtendBundle\Model;

/**
 * Enum Option model.
 */
class EnumOption
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
    protected $isDefault;

    /**
     * @var int
     */
    protected $priority;

    /**
     * @param array $data
     * @return EnumOption
     */
    public static function createFromArray(array $data)
    {
        $instance = new self();

        if (isset($data['id'])) {
            $instance->setId($data['id']);
        }

        if (isset($data['label'])) {
            $instance->setLabel($data['label']);
        }

        if (isset($data['is_default'])) {
            $instance->setIsDefault($data['is_default']);
        }

        if (isset($data['priority'])) {
            $instance->setPriority($data['priority']);
        }

        return $instance;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'is_default' => $this->getIsDefault(),
            'priority' => $this->getPriority()
        ];
    }

    /**
     * @param integer $id
     *
     * @return EnumOption
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $label
     *
     * @return EnumOption
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param boolean $isDefault
     *
     * @return EnumOption
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param integer $priority
     *
     * @return EnumOption
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
