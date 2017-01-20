<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

class BeforeRemoveFieldEvent extends SymfonyEvent
{
    /** @var string */
    protected $className;

    /** @var string */
    protected $fieldName;

    /** @var bool */
    protected $hasErrors = false;

    /** @var string */
    protected $validationMessage = '';

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
     * @param bool $hasErrors
     * @return $this
     */
    public function setHasErrors($hasErrors)
    {
        $this->hasErrors = $hasErrors;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return $this->hasErrors;
    }

    /**
     * @param string $validationMessage
     * @return $this
     */
    public function setValidationMessage($validationMessage)
    {
        $this->validationMessage = $validationMessage;

        return $this;
    }

    /**
     * @return string
     */
    public function getValidationMessage()
    {
        return $this->validationMessage;
    }
}
