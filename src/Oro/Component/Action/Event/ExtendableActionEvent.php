<?php

namespace Oro\Component\Action\Event;

use Oro\Component\Action\Model\AbstractStorage;
use Symfony\Contracts\EventDispatcher\Event;

class ExtendableActionEvent extends Event
{
    public const NAME = 'extendable_action';

    public function __construct(
        protected ?AbstractStorage $context = null
    ) {
    }

    public function getContext(): ?AbstractStorage
    {
        return $this->context;
    }
}
