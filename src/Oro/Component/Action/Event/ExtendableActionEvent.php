<?php

namespace Oro\Component\Action\Event;

use Oro\Component\Action\Model\AbstractStorage;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for extendable action.
 */
class ExtendableActionEvent extends Event
{
    public const NAME = 'extendable_action';

    public function __construct(
        protected ?AbstractStorage $data = null
    ) {
    }

    public function getData(): ?AbstractStorage
    {
        return $this->data;
    }
}
