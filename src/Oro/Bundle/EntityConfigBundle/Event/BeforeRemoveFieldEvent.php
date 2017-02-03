<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

class BeforeRemoveFieldEvent extends SymfonyEvent
{
    /** @var string */
    protected $className;

    /** @var string */
    protected $fieldName;

    /** @var string[] */
    protected $validationMessages = [];

    /**
     * @param string $className The FQCN of an entity
     * @param string $fieldName Field name
     */
    public function __construct($className, $fieldName)
    {
        $this->className = $className;
        $this->fieldName = $fieldName;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param string $validationMessage
     * @return $this
     */
    public function addValidationMessage($validationMessage)
    {
        $this->validationMessages[] = $validationMessage;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getValidationMessages()
    {
        return $this->validationMessages;
    }
}
