<?php

namespace Oro\Component\Action\Tests\Unit\Action\Stub;

use Oro\Component\Action\Action\EventDispatcherAwareActionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DispatcherAwareAction implements EventDispatcherAwareActionInterface
{
    #[\Override]
    public function setDispatcher(EventDispatcherInterface $eventDispatcher)
    {
    }
}
