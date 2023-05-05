<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Layout\Context;

use Oro\Bundle\LayoutBundle\Event\LayoutContextChangedEvent;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\LayoutContextStack as BaseLayoutContextStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Extends {@see BaseLayoutContextStack} to add {@see LayoutContextChangedEvent} dispatching.
 */
class LayoutContextStack extends BaseLayoutContextStack
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function push(?ContextInterface $context): void
    {
        parent::push($context);

        $this->eventDispatcher->dispatch(new LayoutContextChangedEvent($this->getParentContext(), $context));
    }

    public function pop(): ?ContextInterface
    {
        $context = parent::pop();

        $this->eventDispatcher->dispatch(new LayoutContextChangedEvent($context, $this->getCurrentContext()));

        return $context;
    }
}
