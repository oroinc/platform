<?php

namespace Oro\Bundle\BatchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents the invalid item event.
 */
class InvalidItemEvent extends Event implements EventInterface
{
    private string $class;

    private string $reason;

    private array $reasonParameters;

    private array $item;

    public function __construct(string $class, string $reason, array $reasonParameters, array $item)
    {
        $this->class = $class;
        $this->reason = $reason;
        $this->reasonParameters = $reasonParameters;
        $this->item = $item;
    }

    /**
     * Get the class which encountered the invalid item
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Get the reason why the item is invalid
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Get the reason parameters
     */
    public function getReasonParameters(): array
    {
        return $this->reasonParameters;
    }

    /**
     * Get the invalid item
     */
    public function getItem(): array
    {
        return $this->item;
    }
}
