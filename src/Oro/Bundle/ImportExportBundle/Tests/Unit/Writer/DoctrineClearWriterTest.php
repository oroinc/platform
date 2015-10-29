<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter;

class DoctrineClearWriterTest extends \PHPUnit_Framework_TestCase
{
    public function testWrite()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $registry->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('clear');
        $writer = new DoctrineClearWriter($registry);
        $writer->write(array());
    }
}
