<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Entity\Repository\ImportExportResultRepository;
use Oro\Bundle\ImportExportBundle\Manager\ImportExportResultManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ImportExportResultManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var ImportExportResultManager */
    private $importExportResultManager;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->importExportResultManager = new ImportExportResultManager(
            $this->managerRegistry,
            $this->tokenAccessor
        );
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @dataProvider saveResultProvider
     */
    public function testSaveResult($actual, $expected): void
    {
        $expectedResult = $this->getEntity(ImportExportResult::class, $expected);

        /** @var \PHPUnit\Framework\MockObject\MockObject */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($expectedResult);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $this->managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($expected['organization']);

        $importExportResult = $this->importExportResultManager->saveResult(
            $actual['jobId'],
            $actual['type'],
            $actual['entity'],
            $actual['owner'],
            $actual['filename'],
            $actual['options']
        );

        $this->assertEquals($expectedResult, $importExportResult);
    }

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
                    'entity' => 'Acme',
                    'options' => []
                ],
                'expected' => [
                    'jobId' => 123,
                    'type' => 'import_or_export',
                    'filename' => 'file.csv',
                    'entity' => 'Acme',
                    'options' => [],
                    'organization' => $organization
                ],
            ],
            'with owner' => [
                'actual' => [
                    'jobId' => 123,
                    'owner' => $user,
                    'type' => 'import_or_export',
                    'filename' => 'file.csv',
                    'entity' => 'Acme',
                    'options' => []
                ],
                'expected' => [
                    'jobId' => 123,
                    'owner' => $user,
                    'type' => 'import_or_export',
                    'filename' => 'file.csv',
                    'entity' => 'Acme',
                    'options' => [],
                    'organization' => $organization
                ],
            ],
            'with options' => [
                'actual' => [
                    'jobId' => 123,
                    'owner' => null,
                    'type' => 'import_or_export',
                    'filename' => 'file.csv',
                    'entity' => 'Acme',
                    'options' => ['test1' => 'test2']
                ],
                'expected' => [
                    'jobId' => 123,
                    'type' => 'import_or_export',
                    'filename' => 'file.csv',
                    'entity' => 'Acme',
                    'options' => ['test1' => 'test2'],
                    'organization' => $organization
                ],
            ],
        ];
    }

    public function testMarkResultsAsExpired(): void
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $importExportRepository = $this->createMock(ImportExportResultRepository::class);
        $importExportRepository
            ->expects($this->once())
            ->method('updateExpiredRecords')
            ->with($date, $date);

        /** @var \PHPUnit\Framework\MockObject\MockObject */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(ImportExportResult::class)
            ->willReturn($importExportRepository);

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $this->managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->importExportResultManager->markResultsAsExpired($date, $date);
    }
}
