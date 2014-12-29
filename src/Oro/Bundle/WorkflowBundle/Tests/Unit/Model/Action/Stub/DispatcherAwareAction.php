<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action\Stub;

use Oro\Bundle\WorkflowBundle\Model\Action\EventDispatcherAwareActionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DispatcherAwareAction implements EventDispatcherAwareActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function setDispatcher(EventDispatcherInterface $eventDispatcher)
    {
    }
}
