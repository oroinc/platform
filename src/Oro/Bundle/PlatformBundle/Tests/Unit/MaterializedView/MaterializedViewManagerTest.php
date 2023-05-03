<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\MaterializedView;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Entity\MaterializedView as MaterializedViewEntity;
use Oro\Bundle\PlatformBundle\Entity\Repository\MaterializedViewEntityRepository;
use Oro\Bundle\PlatformBundle\MaterializedView\Exception\MaterializedViewAlreadyExistsException;
use Oro\Bundle\PlatformBundle\MaterializedView\Exception\MaterializedViewDoesNotExistException;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewManager;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\DoctrineUtils\DBAL\Schema\MaterializedView;
use Oro\Component\DoctrineUtils\DBAL\Schema\MaterializedViewSchemaManager;
use Oro\Component\DoctrineUtils\MaterializedView\MaterializedViewByQueryFactory;

class MaterializedViewManagerTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /** @var MaterializedViewByQueryFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $materializedViewByQueryFactory;

    /** @var MaterializedViewSchemaManager|\PHPUnit\Framework\MockObject\MockObject */
    private $materializedViewSchemaManager;

    /** @var MaterializedViewEntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    private $unitOfWork;

    /** @var MaterializedViewManager */
    private MaterializedViewManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->materializedViewByQueryFactory = $this->createMock(MaterializedViewByQueryFactory::class);
        $this->materializedViewSchemaManager = $this->createMock(MaterializedViewSchemaManager::class);

        $this->manager = new MaterializedViewManager(
            $managerRegistry,
            $this->materializedViewByQueryFactory,
            $this->materializedViewSchemaManager
        );

        $this->setUpLoggerMock($this->manager);

        $this->entityRepository = $this->createMock(MaterializedViewEntityRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry->expects(self::any())
            ->method('getManagerForClass')
            ->with(MaterializedViewEntity::class)
            ->willReturn($this->entityManager);

        $managerRegistry->expects(self::any())
            ->method('getRepository')
            ->with(MaterializedViewEntity::class)
            ->willReturn($this->entityRepository);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects(self::any())
            ->method('getDefaultQueryHints')
            ->willReturn([]);
        $configuration->expects(self::any())
            ->method('isSecondLevelCacheEnabled')
            ->willReturn(false);

        $this->entityManager->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->entityManager->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);
    }

    public function testCreateByQueryWhenAlreadyExists(): void
    {
        $name = 'already_existing';
        $this->entityRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => $name])
            ->willReturn(new MaterializedViewEntity());

        $this->expectException(MaterializedViewAlreadyExistsException::class);

        $this->manager->createByQuery(new Query($this->entityManager), $name);
    }

    /**
     * @dataProvider createByQueryDataProvider
     */
    public function testCreateByQuery(?string $name, bool $withData): void
    {
        $this->entityRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $query = new Query($this->entityManager);

        $this->materializedViewByQueryFactory->expects(self::once())
            ->method('createByQuery')
            ->with($query, self::isType('string'), $withData)
            ->willReturnCallback(function (Query $query, ?string $name, bool $withData) use (&$generatedName) {
                $materializedViewModel = new MaterializedView($name, 'SELECT 1', $withData);
                $generatedName = $name;

                $this->materializedViewSchemaManager->expects(self::once())
                    ->method('create')
                    ->with($materializedViewModel);

                $this->loggerMock->expects(self::once())
                    ->method('info')
                    ->with(
                        'Created materialized view {name} (with data: {withData}) from ORM query.',
                        [
                            'name' => $name,
                            'withData' => (int)$withData,
                        ]
                    );

                return $materializedViewModel;
            });

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(MaterializedViewEntity::class));

        $this->unitOfWork->expects(self::once())
            ->method('commit')
            ->with(self::isInstanceOf(MaterializedViewEntity::class));

        $materializedViewEntity = $this->manager->createByQuery($query, $name, $withData);
        self::assertEquals($name ?? $generatedName, $materializedViewEntity->getName());
        self::assertSame($withData, $materializedViewEntity->isWithData());
    }

    public function createByQueryDataProvider(): array
    {
        return [
            ['name' => 'sample_name1', 'withData' => true],
            ['name' => 'sample_name2', 'withData' => false],
            ['name' => null, 'withData' => false],
        ];
    }

    public function testDeleteWhenNotExists(): void
    {
        $name = 'sample_name';
        $this->materializedViewSchemaManager->expects(self::once())
            ->method('drop')
            ->with($name);

        $this->entityRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager->expects(self::never())
            ->method(self::anything());

        $this->manager->delete($name);
    }

    public function testDelete(): void
    {
        $name = 'sample_name';
        $this->materializedViewSchemaManager->expects(self::once())
            ->method('drop')
            ->with($name);

        $materializedViewEntity = new MaterializedViewEntity();
        $this->entityRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($materializedViewEntity);

        $this->entityManager->expects(self::once())
            ->method('remove')
            ->with($materializedViewEntity);

        $this->unitOfWork->expects(self::once())
            ->method('commit')
            ->with($materializedViewEntity);

        $this->loggerMock->expects(self::once())
            ->method('info')
            ->with('Deleted materialized view {name}', ['name' => $name]);

        $this->manager->delete($name);
    }

    public function testRefreshWhenNotExists(): void
    {
        $name = 'sample_name';
        $this->entityRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => $name])
            ->willReturn(null);

        $this->expectExceptionObject(MaterializedViewDoesNotExistException::create($name, 'Failed to refresh.'));

        $this->manager->refresh($name);
    }

    /**
     * @dataProvider refreshDataProvider
     */
    public function testRefresh(bool $concurrently, bool $withData): void
    {
        $name = 'sample_name';
        $materializedViewEntity = (new MaterializedViewEntity())
            ->setName($name);

        $this->entityRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => $name])
            ->willReturn($materializedViewEntity);

        $this->materializedViewSchemaManager->expects(self::once())
            ->method('refresh')
            ->with($name, $concurrently, $withData);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function (MaterializedViewEntity $entity) use ($withData) {
                self::assertEquals($withData, $entity->isWithData());
            });

        $this->unitOfWork->expects(self::once())
            ->method('commit')
            ->with(self::isInstanceOf(MaterializedViewEntity::class));

        $this->loggerMock->expects(self::once())
            ->method('info')
            ->with('Refreshed materialized view {name} (with data: {withData})', [
                'name' => $name,
                'concurrently' => (int)$concurrently,
                'withData' => (int)$withData,
            ]);

        $this->manager->refresh($name, $concurrently, $withData);
    }

    public function refreshDataProvider(): array
    {
        return [
            ['concurrently' => false, 'withData' => false],
            ['concurrently' => false, 'withData' => true],
            ['concurrently' => true, 'withData' => true],
            ['concurrently' => true, 'withData' => false],
        ];
    }

    public function testFindByNameWhenNotFound(): void
    {
        $name = 'sample_name';
        $this->entityRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => $name])
            ->willReturn(null);

        self::assertNull($this->manager->findByName($name));
    }

    public function testFindByName(): void
    {
        $name = 'sample_name';
        $materializedViewEntity = (new MaterializedViewEntity())
            ->setName($name);

        $this->entityRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => $name])
            ->willReturn($materializedViewEntity);

        self::assertSame($materializedViewEntity, $this->manager->findByName($name));
    }
}
