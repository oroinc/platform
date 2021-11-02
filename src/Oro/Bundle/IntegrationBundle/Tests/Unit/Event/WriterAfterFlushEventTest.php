<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Event;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\IntegrationBundle\Event\WriterAfterFlushEvent;

class WriterAfterFlushEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $entityManager = $this->createMock(EntityManager::class);

        $event = new WriterAfterFlushEvent($entityManager);

        $this->assertEquals($entityManager, $event->getEntityManager());
    }
}
