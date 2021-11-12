<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter;

class DoctrineClearWriterTest extends \PHPUnit\Framework\TestCase
{
    public function testWrite()
    {
        $entityManager = $this->createMock(EntityManager::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('clear');

        $writer = new DoctrineClearWriter($registry);
        $writer->write([]);
    }
}
