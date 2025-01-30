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
    public const CONTEXT_KEY = '__context__';
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
        $this->initDataByContext();
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

        if ($this->data->offsetExists(self::CONTEXT_KEY)) {
            $this->context = $this->data->offsetGet(self::CONTEXT_KEY);
            $this->data->offsetUnset(self::CONTEXT_KEY);
            // Reset modified flag
            $this->data = new ExtendableEventData($this->data->toArray());
        }
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
