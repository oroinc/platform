<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Entity\Repository\ImportExportResultRepository;
use Oro\Bundle\ImportExportBundle\Manager\ImportExportResultManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ImportExportResultManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var ImportExportResultManager */
    private $importExportResultManager;

    protected function setUp(): void
    {
        $this->managerRegistry = self::createMock(ManagerRegistry::class);
        $this->importExportResultManager = new ImportExportResultManager($this->managerRegistry);
    }

    /**
     * @param $actual
     * @param $expected
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @dataProvider saveResultProvider
     */
    public function testSaveResult($actual, $expected): void
    {
        $importExportResult = new ImportExportResult();
        $importExportResult
            ->setJobId($expected['jobId'])
            ->setType($expected['type'])
            ->setFilename($expected['filename'])
            ->setEntity($expected['entity']);

        /** @var \PHPUnit\Framework\MockObject\MockObject */
        $entityManager = self::createMock(EntityManager::class);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($importExportResult);
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $this->managerRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $importExportResult = $this->importExportResultManager->saveResult(
            $actual['jobId'],
            $actual['type'],
            $actual['entity'],
            $actual['owner'],
            $actual['filename']
        );

        self::assertAttributeEquals($expected['jobId'], 'jobId', $importExportResult);
        self::assertAttributeEquals($expected['type'], 'type', $importExportResult);
        self::assertAttributeEquals($expected['filename'], 'filename', $importExportResult);
        self::assertAttributeEquals($expected['owner'], 'owner', $importExportResult);
        self::assertAttributeEquals($expected['entity'], 'entity', $importExportResult);
    }

    /**
     * @return array
     */
    public function saveResultProvider(): array
    {
        $user = new User();
        $organization = new Organization();
        $user->setOrganization($organization);

        return [
            'without owner' => [
                'actual' => [
                    'jobId' => 123,
                    'owner' => null,
                    'type' => 'import_or_export',
                    'filename' => 'file.csv',
                    'entity' => 'Acme'
                ],
                'expected' => [
                    'jobId' => 123,
                    'owner' => null,
                    'type' => 'import_or_export',
                    'filename' => 'file.csv',
                    'entity' => 'Acme'
                ],
            ],
            'with owner' => [
                'actual' => [
                    'jobId' => 123,
                    'owner' => $user,
                    'type' => 'import_or_export',
                    'filename' => 'file.csv',
                    'entity' => 'Acme'
                ],
                'expected' => [
                    'jobId' => 123,
                    'owner' => $user,
                    'type' => 'import_or_export',
                    'filename' => 'file.csv',
                    'entity' => 'Acme'
                ],
            ],
        ];
    }

    public function testMarkResultsAsExpired(): void
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $importExportRepository = self::createMock(ImportExportResultRepository::class);
        $importExportRepository
            ->expects(self::once())
            ->method('updateExpiredRecords')
            ->with($date, $date);

        /** @var \PHPUnit\Framework\MockObject\MockObject */
        $entityManager = self::createMock(EntityManager::class);
        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(ImportExportResult::class)
            ->willReturn($importExportRepository);

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $this->managerRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->importExportResultManager->markResultsAsExpired($date, $date);
    }
}
