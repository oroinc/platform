<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class ValueRenderEvent extends Event
{
    /**
     * Current entity
     *
     * @var object
     */
    protected $entity;

    /**
     * Entity field value
     *
     * @var mixed
     */
    protected $fieldValue;

    /**
     * Entity field config id
     *
     * @var FieldConfigId
     */
    protected $fieldConfigId;

    /**
     * Value, which will be used for field display
     *
     * @var mixed
     */
    protected $fieldViewValue;

    /**
     * @var bool
     */
    protected $fieldVisibility = true;

    /**
     * @param object $entity
     * @param mixed $fieldValue
     * @param FieldConfigId $fieldConfigId
     */
    public function __construct($entity, $fieldValue, FieldConfigId $fieldConfigId)
    {
        $this->entity = $entity;
        $this->fieldValue = $fieldValue;
        $this->fieldViewValue = $fieldValue;
        $this->fieldConfigId = $fieldConfigId;
    }

    /**
     * @return FieldConfigId
     */
    public function getFieldConfigId()
    {
        return $this->fieldConfigId;
    }

    /**
     * @return mixed
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * @return mixed
     */
    public function getFieldViewValue()
    {
        return $this->fieldViewValue;
    }

    /**
     * @param mixed $fieldViewValue
     *
     * @return ValueRenderEvent
     */
    public function setFieldViewValue($fieldViewValue)
    {
        $this->fieldViewValue = $fieldViewValue;

        return $this;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return boolean
     */
    public function isFieldVisible()
    {
        return $this->fieldVisibility;
    }

    /**
     * @param boolean $fieldVisibility
     */
    public function setFieldVisibility($fieldVisibility)
    {
        $this->fieldVisibility = $fieldVisibility;
    }
}
