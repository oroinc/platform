<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Event;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\IntegrationBundle\Event\WriterAfterFlushEvent;
use PHPUnit\Framework\TestCase;

class WriterAfterFlushEventTest extends TestCase
{
    public function testEvent(): void
    {
        $entityManager = $this->createMock(EntityManager::class);

        $event = new WriterAfterFlushEvent($entityManager);

        $this->assertEquals($entityManager, $event->getEntityManager());
    }
}
