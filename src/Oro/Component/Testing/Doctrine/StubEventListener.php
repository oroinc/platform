<?php

namespace Oro\Component\Testing\Doctrine;

use Doctrine\ORM\Event;

abstract class StubEventListener
{
    abstract public function postFlush(Event\PostFlushEventArgs $args);

    abstract public function onFlush(Event\OnFlushEventArgs $args);
}
