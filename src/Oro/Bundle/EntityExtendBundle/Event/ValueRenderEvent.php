<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class ValueRenderEvent extends Event
{
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
    public function __construct($fieldValue, FieldConfigId $fieldConfigId)
    {
        $this->fieldValue = $fieldValue;
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
}
