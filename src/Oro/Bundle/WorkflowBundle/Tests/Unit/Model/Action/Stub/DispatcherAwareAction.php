<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action\Stub;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WorkflowBundle\Model\Action\EventDispatcherAwareActionInterface;

class DispatcherAwareAction implements EventDispatcherAwareActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function setDispatcher(EventDispatcherInterface $eventDispatcher)
    {
    }
}
