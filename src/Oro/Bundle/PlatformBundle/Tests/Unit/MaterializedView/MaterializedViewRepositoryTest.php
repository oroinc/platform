<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\MaterializedView;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewRepository;

class MaterializedViewRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testCreateQueryBuilder(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $materializedViewName = 'sample_name';
        $rootAlias = 'sample_alias';

        $repository = new MaterializedViewRepository($this->entityManager, $materializedViewName);

        self::assertEquals(
            (new QueryBuilder($connection))->from($materializedViewName, $rootAlias),
            $repository->createQueryBuilder($rootAlias)
        );
    }

    public function testCreateQueryBuilderWhenUnsafeName(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $materializedViewName = 'sample name';
        $rootAlias = 'sample_alias';

        $repository = new MaterializedViewRepository($this->entityManager, $materializedViewName);

        $this->expectExceptionObject(new \InvalidArgumentException('Unsafe value passed ' . $materializedViewName));

        self::assertEquals(
            (new QueryBuilder($connection))->from($materializedViewName, $rootAlias),
            $repository->createQueryBuilder($rootAlias)
        );
    }

    public function testCreateQueryBuilderWhenUnsafeRootAlias(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $materializedViewName = 'sample_name';
        $rootAlias = 'sample alias';

        $repository = new MaterializedViewRepository($this->entityManager, $materializedViewName);

        $this->expectExceptionObject(new \InvalidArgumentException('Unsafe value passed ' . $rootAlias));

        self::assertEquals(
            (new QueryBuilder($connection))->from($materializedViewName, $rootAlias),
            $repository->createQueryBuilder($rootAlias)
        );
    }
}
