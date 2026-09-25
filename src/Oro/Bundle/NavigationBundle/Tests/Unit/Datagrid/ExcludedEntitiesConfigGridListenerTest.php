<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\NavigationBundle\Datagrid\ExcludedEntitiesConfigGridListener;
use PHPUnit\Framework\TestCase;

class ExcludedEntitiesConfigGridListenerTest extends TestCase
{
    public function testOnBuildAfterExcludesGivenEntities(): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $excludedEntities = ['Acme\Bundle\AcmeBundle\Entity\First', 'Acme\Bundle\AcmeBundle\Entity\Second'];

        (new ExcludedEntitiesConfigGridListener($excludedEntities))
            ->onBuildAfter($this->getEvent($queryBuilder));

        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        $parameter = $parameters->first();
        self::assertEquals($excludedEntities, $parameter->getValue());
        self::assertStringEndsWith(
            \sprintf(' AND ce.className NOT IN(:%s)', $parameter->getName()),
            $queryBuilder->getDQL()
        );
    }

    public function testOnBuildAfterWhenNoEntityIsExcluded(): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $dql = $queryBuilder->getDQL();

        (new ExcludedEntitiesConfigGridListener([]))->onBuildAfter($this->getEvent($queryBuilder));

        self::assertSame($dql, $queryBuilder->getDQL());
        self::assertCount(0, $queryBuilder->getParameters());
    }

    public function testOnBuildAfterWhenDatasourceIsNotOrm(): void
    {
        $this->expectNotToPerformAssertions();

        (new ExcludedEntitiesConfigGridListener(['Acme\Bundle\AcmeBundle\Entity\First']))
            ->onBuildAfter($this->getEvent(null));
    }

    private function getQueryBuilder(): QueryBuilder
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getExpressionBuilder')
            ->willReturn(new Expr());

        return (new QueryBuilder($entityManager))
            ->select('ce.id')
            ->from(EntityConfigModel::class, 'ce')
            ->where("ce.mode <> 'hidden'");
    }

    private function getEvent(?QueryBuilder $queryBuilder): BuildAfter
    {
        if (null === $queryBuilder) {
            $datasource = $this->createMock(DatasourceInterface::class);
        } else {
            $datasource = $this->createMock(OrmDatasource::class);
            $datasource->expects(self::any())
                ->method('getQueryBuilder')
                ->willReturn($queryBuilder);
        }

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::any())
            ->method('getDatasource')
            ->willReturn($datasource);

        return new BuildAfter($datagrid);
    }
}
