<?php

namespace Oro\Component\Action\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Action\Model\AbstractStorage;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for extendable condition.
 */
class ExtendableConditionEvent extends Event
{
    public const NAME = 'extendable';

    /**
     * @var ArrayCollection
     */
    protected $errors;

    /**
     * @param null|mixed $context
     */
    public function __construct(
        protected ?AbstractStorage $data = null
    ) {
        $this->errors = new ArrayCollection();
    }

    public function getData(): ?AbstractStorage
    {
        return $this->data;
    }

    public function addError(string $errorMessage, mixed $errorContext = null): self
    {
        $this->errors->add(['message' => $errorMessage, 'context' => $errorContext]);

        return $this;
    }

    public function getErrors(): ArrayCollection
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !$this->errors->isEmpty();
    }
}
