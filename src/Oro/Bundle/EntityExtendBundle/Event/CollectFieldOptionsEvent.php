<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class CollectFieldOptionsEvent extends Event
{
    const EVENT_NAME = "oro_entity_extend.collect_field_options";

    /** @var array */
    protected $options;

    /** @var FieldConfigModel */
    protected $fieldConfigModel;

    /**
     * @param array            $options
     * @param FieldConfigModel $fieldConfigModel
     */
    public function __construct($options, FieldConfigModel $fieldConfigModel)
    {
        $this->options          = $options;
        $this->fieldConfigModel = $fieldConfigModel;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return FieldConfigModel
     */
    public function getFieldConfigModel()
    {
        return $this->fieldConfigModel;
    }
}
