<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Event;

use Oro\Bundle\IntegrationBundle\Event\WriterAfterFlushEvent;

class WriterAfterFlushEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new WriterAfterFlushEvent($entityManager);

        $this->assertEquals($entityManager, $event->getEntityManager());
    }
}
