<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\EventListener\Stub;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\EntityBundle\EventListener\DoctrineFlushProgressListener;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * This listener is used by DoctrineFlushProgressListenerTest to check that
 * isFlushInProgress returns true between preFlush and postFlush events
 */
class TestFlushCheckListener
{
    /** @var DoctrineFlushProgressListener */
    private $listener;

    /** @var WebTestCase */
    private $webTestCase;

    public function __construct(DoctrineFlushProgressListener $listener, WebTestCase $webTestCase)
    {
        $this->listener = $listener;
        $this->webTestCase = $webTestCase;
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        $this->webTestCase->assertTrue($this->listener->isFlushInProgress($event->getEntityManager()));
    }
}
