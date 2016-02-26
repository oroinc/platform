<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Action\Stub;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\Action\Action\EventDispatcherAwareActionInterface;

class DispatcherAwareAction implements EventDispatcherAwareActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function setDispatcher(EventDispatcherInterface $eventDispatcher)
    {
    }
}
