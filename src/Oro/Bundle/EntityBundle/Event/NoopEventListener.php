<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Event;

/**
 * No-operation event listener returned by {@see OroEntityListenerResolver} when the original listener is disabled.
 * This class safely absorbs all method calls without performing any actions.
 */
class NoopEventListener
{
    public function __call(string $name, array $arguments)
    {
        // Noop.
    }
}
