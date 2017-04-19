<?php

namespace Oro\Component\Testing\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;

class EntityManagerMockBuilder
{
    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     * @param array                       $insertions
     * @param array                       $updates
     * @param array                       $deletions
     * @param array                       $entityChangeSet
     * @return EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getEntityManager(
        \PHPUnit_Framework_TestCase $testCase,
        array $insertions = [],
        array $updates = [],
        array $deletions = [],
        array $entityChangeSet = []
    ) {
        $unitOfWork = $this->getMockBuilder($testCase, UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertions);
        $unitOfWork
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $unitOfWork
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletions);
        $unitOfWork
            ->method('getEntityChangeSet')
            ->willReturn($entityChangeSet);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->getMockBuilder($testCase, EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        return $entityManager;
    }

    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     * @param  string                     $className
     * @return \PHPUnit_Framework_MockObject_MockBuilder
     */
    protected function getMockBuilder(\PHPUnit_Framework_TestCase $testCase, $className)
    {
        return new \PHPUnit_Framework_MockObject_MockBuilder($testCase, $className);
    }
}
