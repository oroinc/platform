<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ThemeBundle\Entity\Repository\ThemeConfigurationRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ThemeConfigurationRepositoryTest extends TestCase
{
    private ThemeConfigurationRepository $repository;

    private EntityManagerInterface|MockObject $entityManager;

    private ClassMetadata|MockObject $classMetadata;

    private QueryBuilder|MockObject $queryBuilder;

    private AbstractQuery|MockObject $query;

    private Expr|MockObject $queryExpression;

    private Comparison|MockObject $comparison;

    public function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(AbstractQuery::class);
        $this->queryExpression = $this->createMock(Expr::class);
        $this->comparison = $this->createMock(Comparison::class);

        $this->repository = new ThemeConfigurationRepository($this->entityManager, $this->classMetadata);
    }

    public function testGetThemeByThemeConfigurationId(): void
    {
        $expectedTheme = 'default';
        $this->setUpQueryBuilder();
        $this->setUpQuery();

        $this->query->expects(self::any())
            ->method('getOneOrNullResult')
            ->willReturn('default');

        $result = $this->repository->getThemeByThemeConfigurationId(1);

        self::assertEquals($expectedTheme, $result);
    }

    public function testGetThemeWithoutId(): void
    {
        self::assertNull($this->repository->getThemeByThemeConfigurationId(null));
    }

    private function setUpQueryBuilder(): void
    {
        $this->entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
    }

    private function setUpQuery(): void
    {
        $this->queryBuilder->expects(self::exactly(2))
            ->method('select')
            ->willReturnSelf();

        $this->queryBuilder->expects(self::once())
            ->method('from')
            ->willReturnSelf();

        $this->queryBuilder->expects(self::once())
            ->method('expr')
            ->willReturn($this->queryExpression);

        $this->queryExpression->expects(self::once())
            ->method('eq')
            ->with('tc.id', ':id')
            ->willReturn($this->comparison);

        $this->queryBuilder->expects(self::once())
            ->method('where')
            ->willReturnSelf();

        $this->queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('id', 1, 'integer')
            ->willReturnSelf();

        $this->queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($this->query);
    }
}
