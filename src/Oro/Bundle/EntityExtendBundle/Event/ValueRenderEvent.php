<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class ValueRenderEvent extends Event
{
    /**
     * @var object
     */
    protected $entity;

    /**
     * @var mixed
     */
    protected $fieldValue;

    /**
     * @var FieldConfigId
     */
    protected $fieldConfigId;

    /**
     * @var mixed
     */
    protected $fieldViewValue;

    /**
     * @param mixed         $fieldValue
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
}
