<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Manager\ImportExportResultManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ImportExportResultManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ImportExportResultManager */
    private $resultManager;

    protected function setUp(): void
    {
        $this->registry = self::createMock(ManagerRegistry::class);
        $this->resultManager = new ImportExportResultManager($this->registry);
    }

    public function testSaveResult(): void
    {
        $jobId = 1;
        $jobCode = 'JobCode-1';
        $fileName = 'ACME';

        $importExportResult = new ImportExportResult();
        $importExportResult
            ->setJobId($jobId)
            ->setJobCode($jobCode)
            ->setFilename($fileName);

        /** @var \PHPUnit\Framework\MockObject\MockObject */
        $entityManager = self::createMock(EntityManager::class);
        $this->registry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($importExportResult);

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $importExportResult = $this->resultManager->saveResult($jobId, $jobCode, $fileName);

        self::assertAttributeEquals($fileName, 'filename', $importExportResult);
        self::assertAttributeEquals($jobId, 'jobId', $importExportResult);
        self::assertAttributeEquals($jobCode, 'jobCode', $importExportResult);
    }
}
