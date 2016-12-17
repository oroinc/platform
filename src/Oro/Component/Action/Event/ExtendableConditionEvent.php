<?php

namespace Oro\Component\Action\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\Event;

class ExtendableConditionEvent extends Event
{
    const NAME = 'extendable';

    /**
     * @var null|mixed
     */
    protected $context;

    /**
     * @var ArrayCollection
     */
    protected $errors;

    /**
     * @param null|mixed $context
     */
    public function __construct($context = null)
    {
        $this->context = $context;
        $this->errors = new ArrayCollection();
    }

    /**
     * @return null|mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     * @return $this
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @param string $errorMessage
     * @param mixed $errorContext
     * @return $this
     */
    public function addError($errorMessage, $errorContext = null)
    {
        $this->errors->add(['message' => $errorMessage, 'context' => $errorContext]);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }
}
