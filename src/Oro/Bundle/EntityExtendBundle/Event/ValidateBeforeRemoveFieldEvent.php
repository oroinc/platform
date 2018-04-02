<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\EventDispatcher\Event;

class ValidateBeforeRemoveFieldEvent extends Event
{
    const NAME = 'oro.entity_extend.field.validate.before_remove';

    /** @var FieldConfigModel */
    protected $fieldConfig;

    /** @var array */
    protected $validationMessages = [];

    /**
     * @param FieldConfigModel $fieldConfig
     */
    public function __construct(FieldConfigModel $fieldConfig)
    {
        $this->fieldConfig = $fieldConfig;
    }

    /**
     * @return FieldConfigModel
     */
    public function getFieldConfig()
    {
        return $this->fieldConfig;
    }

    /**
     * @return array
     */
    public function getValidationMessages()
    {
        return $this->validationMessages;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function addValidationMessage($message)
    {
        $this->validationMessages[] = $message;

        return $this;
    }
}
