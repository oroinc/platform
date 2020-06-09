<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Oro\Bundle\EntityBundle\EventListener\DoctrineFlushProgressListener;
use Oro\Bundle\EntityBundle\Tests\Functional\EventListener\Stub\TestFlushCheckListener;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DoctrineFlushProgressListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testIsFlushInProgress()
    {
        $container = $this->getContainer();

        /** @var DoctrineFlushProgressListener $listener */
        $listener = $container->get('oro_entity.tests.event_listener.doctrine_flush_progress_listener');
        $doctrine = $container->get('doctrine');

        /** @var EntityManager $em */
        $em = $doctrine->getManager();

        $em->getEventManager()->addEventListener(Events::onFlush, new TestFlushCheckListener($listener, $this));

        $em->flush();

        // Number of assertions must be greater than 0. Otherwise test doesn't check anything
        $this->assertGreaterThan(0, $this->getCount(), 'No any assertions done');
    }
}
