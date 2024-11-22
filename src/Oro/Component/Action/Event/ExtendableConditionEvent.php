<?php

namespace Oro\Component\Action\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Action\Model\AbstractStorage;
use Oro\Component\Action\Model\ActionDataStorageAwareInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for extendable condition.
 */
class ExtendableConditionEvent extends Event
{
    const NAME = 'extendable';

    /**
     * @var null|AbstractStorage
     */
    protected $context;

    /**
     * @var ArrayCollection
     */
    protected $errors;

    private ?AbstractStorage $data = null;

    /**
     * @param null|AbstractStorage $context
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

    public function getData(): ?AbstractStorage
    {
        if (null === $this->data) {
            $this->initDataByContext();
        }

        return $this->data;
    }

    public function setData(AbstractStorage $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * BC layer, will be replaced with data passed from the outer code like ExtendableCondition
     */
    private function initDataByContext()
    {
        if (null === $this->context) {
            return;
        }

        $dataArray = [];
        if ($this->context instanceof ActionDataStorageAwareInterface) {
            $dataArray = $this->context->getActionDataStorage()->toArray();
        } elseif ($this->context instanceof AbstractStorage) {
            $dataArray = $this->context->toArray();
        } elseif (\is_array($this->context)) {
            $dataArray = $this->context;
        }

        $this->data = new ExtendableEventData($dataArray);
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
        return !$this->errors->isEmpty();
    }
}
