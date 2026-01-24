<?php

namespace Oro\Component\Testing\Doctrine;

use Doctrine\ORM\Event;

/**
 * Provides a stub implementation for Doctrine event listeners in tests.
 *
 * This abstract class defines the interface for event listeners that handle Doctrine ORM events.
 * It is intended for use in testing scenarios where event listener behavior needs to be stubbed or mocked.
 * Subclasses must implement the `postFlush()` and `onFlush()` methods
 * to handle the corresponding Doctrine lifecycle events.
 */
abstract class StubEventListener
{
    abstract public function postFlush(Event\PostFlushEventArgs $args);

    abstract public function onFlush(Event\OnFlushEventArgs $args);
}
