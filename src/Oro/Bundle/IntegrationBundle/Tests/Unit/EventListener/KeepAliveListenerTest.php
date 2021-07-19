<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\EventListener;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Job\DoctrineJobRepository;
use Oro\Bundle\IntegrationBundle\Event\WriterAfterFlushEvent;
use Oro\Bundle\IntegrationBundle\EventListener\KeepAliveListener;

class KeepAliveListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnWriterAfterFlush()
    {
        $expectedDql = 'SELECT e.id FROM ' . JobExecution::class . ' e WHERE e.id = 1';

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('execute');

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('createQuery')
            ->with($expectedDql)
            ->willReturn($query);

        $batchJobRepository = $this->createMock(DoctrineJobRepository::class);
        $batchJobRepository->expects($this->once())
            ->method('getJobManager')
            ->willReturn($entityManager);

        $event = $this->createMock(WriterAfterFlushEvent::class);

        $eventListener = new KeepAliveListener($batchJobRepository);
        $eventListener->onWriterAfterFlush($event);
    }
}
