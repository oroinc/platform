<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;

class OrmSorterExtensionTest extends AbstractSorterExtensionTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = new OrmSorterExtension($this->sortersStateProvider, $this->resolver);
    }

    public function testVisitDatasourceWithoutDefaultSorting(): void
    {
        $this->configureResolver();
        $this->sortersStateProvider->expects(self::once())
            ->method('getStateFromParameters')
            ->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with('Test\Entity')
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $qb = new QueryBuilder($em);
        $qb->select('e.id')->from('Test\Entity', 'e');

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource(
            DatagridConfiguration::create(['sorters' => ['columns' => []]]),
            $datasource
        );

        self::assertEquals(
            'SELECT e.id FROM Test\Entity e ORDER BY e.id ASC',
            $qb->getDQL()
        );
    }

    public function testVisitDatasourceWhenQueryAlreadyHasOrderBy(): void
    {
        $this->configureResolver();
        $this->sortersStateProvider->expects(self::once())
            ->method('getStateFromParameters')
            ->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())
            ->method('getClassMetadata');

        $qb = new QueryBuilder($em);
        $qb->select('e.id')->from('Test\Entity', 'e')->addOrderBy('e.name');

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource(
            DatagridConfiguration::create(['sorters' => ['columns' => []]]),
            $datasource
        );

        self::assertEquals(
            'SELECT e.id FROM Test\Entity e ORDER BY e.name ASC',
            $qb->getDQL()
        );
    }

    public function testVisitDatasourceWithoutDefaultSortingForEmptyQuery(): void
    {
        $this->configureResolver();
        $this->sortersStateProvider->expects(self::once())
            ->method('getStateFromParameters')
            ->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())
            ->method('getClassMetadata');

        $qb = new QueryBuilder($em);

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource(
            DatagridConfiguration::create(['sorters' => ['columns' => []]]),
            $datasource
        );

        self::assertEquals(
            [],
            $qb->getDQLPart('orderBy')
        );
    }

    public function testVisitDatasourceWithoutDefaultSortingAndGroupBy(): void
    {
        $this->configureResolver();
        $this->sortersStateProvider->expects(self::once())
            ->method('getStateFromParameters')
            ->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())
            ->method('getClassMetadata');

        $qb = new QueryBuilder($em);
        $qb->select('e.id')->from('Test\Entity', 'e')->groupBy('e.name');

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource(
            DatagridConfiguration::create(['sorters' => ['columns' => []]]),
            $datasource
        );

        self::assertEquals(
            'SELECT e.id FROM Test\Entity e GROUP BY e.name ORDER BY e.name ASC',
            $qb->getDQL()
        );
    }

    public function testVisitDatasourceWithoutDefaultSortingAndMultipleGroupBy(): void
    {
        $this->configureResolver();
        $this->sortersStateProvider->expects(self::once())
            ->method('getStateFromParameters')
            ->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())
            ->method('getClassMetadata');

        $qb = new QueryBuilder($em);
        $qb->select('e.id')->from('Test\Entity', 'e')->addGroupBy('e.id')->addGroupBy('e.name');

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource(
            DatagridConfiguration::create(['sorters' => ['columns' => []]]),
            $datasource
        );

        self::assertEquals(
            'SELECT e.id FROM Test\Entity e GROUP BY e.id, e.name ORDER BY e.id ASC',
            $qb->getDQL()
        );
    }
}
