<?php

namespace Oro\Component\Action\Event;

use Symfony\Component\EventDispatcher\Event;

class ExtendableActionEvent extends Event
{
    const NAME = 'extendable_action';

    /**
     * @var null|mixed
     */
    protected $context;

    /**
     * @param null|mixed $context
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
}
