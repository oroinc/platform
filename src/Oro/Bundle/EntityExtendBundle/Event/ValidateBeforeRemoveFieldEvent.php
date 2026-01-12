<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before a field is removed from an extended entity.
 *
 * This event allows listeners to validate whether a field can be safely removed and to
 * collect any validation messages that should prevent the removal. Listeners can add
 * validation messages that will be displayed to the user before the field deletion is
 * confirmed.
 */
class ValidateBeforeRemoveFieldEvent extends Event
{
    public const NAME = 'oro.entity_extend.field.validate.before_remove';

    /** @var FieldConfigModel */
    protected $fieldConfig;

    /** @var array */
    protected $validationMessages = [];

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
