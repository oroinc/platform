<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Event;

use Oro\Component\Layout\ContextInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after the layout context is changed.
 */
class LayoutContextChangedEvent extends Event
{
    private ?ContextInterface $previousContext;

    private ?ContextInterface $currentContext;

    public function __construct(?ContextInterface $previousContext, ?ContextInterface $currentContext)
    {
        $this->previousContext = $previousContext;
        $this->currentContext = $currentContext;
    }

    public function getPreviousContext(): ?ContextInterface
    {
        return $this->previousContext;
    }

    public function getCurrentContext(): ?ContextInterface
    {
        return $this->currentContext;
    }
}
