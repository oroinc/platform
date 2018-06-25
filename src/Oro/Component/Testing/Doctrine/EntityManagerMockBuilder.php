<?php

namespace Oro\Component\Testing\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;

class EntityManagerMockBuilder
{
    /**
     * @param \PHPUnit\Framework\TestCase $testCase
     * @param array                       $insertions
     * @param array                       $updates
     * @param array                       $deletions
     * @param array                       $entityChangeSet
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getEntityManager(
        \PHPUnit\Framework\TestCase $testCase,
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

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->getMockBuilder($testCase, EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        return $entityManager;
    }

    /**
     * @param \PHPUnit\Framework\TestCase $testCase
     * @param  string                     $className
     * @return \PHPUnit\Framework\MockObject\MockBuilder
     */
    protected function getMockBuilder(\PHPUnit\Framework\TestCase $testCase, $className)
    {
        return new \PHPUnit\Framework\MockObject\MockBuilder($testCase, $className);
    }
}
