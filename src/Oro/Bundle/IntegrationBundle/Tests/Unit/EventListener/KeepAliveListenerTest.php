<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\EventListener;

use Oro\Bundle\IntegrationBundle\EventListener\KeepAliveListener;

class KeepAliveListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnWriterAfterFlush()
    {
        $expectedDql = 'SELECT e.id FROM AkeneoBatchBundle:JobExecution e WHERE e.id = 1';

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('execute'))
            ->getMockForAbstractClass();
        $query->expects($this->once())->method('execute');

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())->method('createQuery')->with($expectedDql)
            ->will($this->returnValue($query));

        $batchJobRepository = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $batchJobRepository->expects($this->once())->method('getJobManager')
            ->will($this->returnValue($entityManager));

        $event = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Event\WriterAfterFlushEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $eventListener = new KeepAliveListener($batchJobRepository);
        $eventListener->onWriterAfterFlush($event);
    }
}
