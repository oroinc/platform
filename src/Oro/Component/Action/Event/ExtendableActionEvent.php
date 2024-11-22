<?php

namespace Oro\Component\Action\Event;

use Oro\Component\Action\Model\AbstractStorage;
use Oro\Component\Action\Model\ActionDataStorageAwareInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for extendable action.
 */
class ExtendableActionEvent extends Event
{
    public const NAME = 'extendable_action';

    /**
     * @var null|mixed
     */
    protected $context;

    private ?AbstractStorage $data = null;

    /**
     * @param null|AbstractStorage $context
     */
    public function __construct($context = null)
    {
        $this->context = $context;
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
     * BC layer, will be replaced with data passed from the outer code like ExtendableAction
     */
    private function initDataByContext()
    {
        if (null === $this->context) {
            return;
        }

        if ($this->context instanceof ActionDataStorageAwareInterface) {
            $this->data = $this->context->getActionDataStorage();
        } elseif ($this->context instanceof AbstractStorage) {
            $this->data = $this->context;
        } elseif (\is_array($this->context)) {
            $this->data = new ExtendableEventData($this->context);
        }
    }
}
