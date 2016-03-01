<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter;

class DoctrineClearWriterTest extends \PHPUnit_Framework_TestCase
{
    public function testWrite()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('clear');
        $writer = new DoctrineClearWriter($registry);
        $writer->write(array());
    }
}
