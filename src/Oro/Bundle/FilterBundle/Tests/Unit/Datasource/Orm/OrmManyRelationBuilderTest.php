<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmManyRelationBuilder;
use Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm\Fixtures\Entity\TestComment;
use Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm\Fixtures\Entity\TestOrder;
use Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm\Fixtures\Entity\TestProduct;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class OrmManyRelationBuilderTest extends OrmTestCase
{
    private EntityManagerInterface $em;
    private OrmManyRelationBuilder $builder;
    private int $paramIndex;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->paramIndex = 0;
        $this->builder = new OrmManyRelationBuilder($doctrine);
    }

    public function testSupports()
    {
        $this->assertTrue(
            $this->builder->supports(
                new OrmFilterDatasourceAdapter($this->em->createQueryBuilder())
            )
        );
        $this->assertFalse(
            $this->builder->supports(
                $this->createMock(FilterDatasourceAdapterInterface::class)
            )
        );
    }

    /**
     * @dataProvider inverseProvider
     */
    public function testBuildComparisonExprSimple(bool $inverse)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id')
            ->from(TestOrder::class, 'o');

        $ds = $this->getFilterDatasourceAdapter($qb);
        $expr = $this->builder->buildComparisonExpr($ds, 'o.products', 'param1', 'test', $inverse);

        $qb->where($expr);
        $result = $qb->getDQL();

        $operator = $inverse ? 'NOT IN' : 'IN';
        $this->assertEquals(
            'SELECT o.id FROM ' . TestOrder::class . ' o'
            . ' WHERE o ' . $operator . '('
            . 'SELECT filter_param1'
            . ' FROM ' . TestOrder::class . ' filter_param1'
            . ' INNER JOIN filter_param1.products filter_param1_rel'
            . ' WHERE filter_param1_rel IN(:param1))',
            $result
        );
    }

    /**
     * @dataProvider inverseProvider
     */
    public function testBuildNullValueExprSimple(bool $inverse)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id')
            ->from(TestOrder::class, 'o');

        $ds = $this->getFilterDatasourceAdapter($qb);
        $expr = $this->builder->buildNullValueExpr($ds, 'o.products', 'test', $inverse);

        $qb->where($expr);
        $result = $qb->getDQL();

        $operator = $inverse ? 'IS NOT' : 'IS';
        $this->assertEquals(
            'SELECT o.id FROM ' . TestOrder::class . ' o'
            . ' WHERE o IN('
            . 'SELECT null_filter_test'
            . ' FROM ' . TestOrder::class . ' null_filter_test'
            . ' LEFT JOIN null_filter_test.products null_filter_test_rel'
            . ' WHERE null_filter_test_rel ' . $operator . ' NULL)',
            $result
        );
    }

    /**
     * @dataProvider inverseProvider
     */
    public function testBuildComparisonExprWithSimpleJoin(bool $inverse)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id, p1.id')
            ->from(TestOrder::class, 'o')
            ->leftJoin('o.products', 'p');

        $ds = $this->getFilterDatasourceAdapter($qb);
        $expr = $this->builder->buildComparisonExpr($ds, 'p.notes', 'param1', 'test', $inverse);

        $qb->where($expr);
        $result = $qb->getDQL();

        $operator = $inverse ? 'NOT IN' : 'IN';
        $this->assertEquals(
            'SELECT o.id, p1.id FROM ' . TestOrder::class . ' o'
            . ' LEFT JOIN o.products p'
            . ' WHERE p ' . $operator . '('
            . 'SELECT filter_param1'
            . ' FROM ' . TestProduct::class . ' filter_param1'
            . ' INNER JOIN filter_param1.notes filter_param1_rel'
            . ' WHERE filter_param1_rel IN(:param1))',
            $result
        );
    }

    /**
     * @dataProvider inverseProvider
     */
    public function testBuildComparisonExprWithUnidirectionalJoin(bool $inverse)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id, p1.id')
            ->from(TestOrder::class, 'o')
            ->leftJoin('o.products', 'p')
            ->leftJoin(TestComment::class, 'c', 'WITH', 'c.products = p AND p.id = 5')
            ->leftJoin('c.products', 'p1');

        $ds = $this->getFilterDatasourceAdapter($qb);
        $expr = $this->builder->buildComparisonExpr($ds, 'p1.orders', 'param1', 'test', $inverse);

        $qb->where($expr);
        $result = $qb->getDQL();

        $operator = $inverse ? 'NOT IN' : 'IN';
        $this->assertEquals(
            'SELECT o.id, p1.id FROM ' . TestOrder::class . ' o'
            . ' LEFT JOIN o.products p'
            . ' LEFT JOIN ' . TestComment::class . ' c WITH c.products = p AND p.id = 5'
            . ' LEFT JOIN c.products p1'
            . ' WHERE p1 ' . $operator . '('
            . 'SELECT filter_param1'
            . ' FROM ' . TestProduct::class . ' filter_param1'
            . ' INNER JOIN filter_param1.orders filter_param1_rel'
            . ' WHERE filter_param1_rel IN(:param1))',
            $result
        );
    }

    public function inverseProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }

    private function getFilterDatasourceAdapter(QueryBuilder $qb): OrmFilterDatasourceAdapter
    {
        $ds = $this->getMockBuilder(OrmFilterDatasourceAdapter::class)
            ->onlyMethods(['generateParameterName'])
            ->setConstructorArgs([$qb])
            ->getMock();
        $ds->expects($this->any())
            ->method('generateParameterName')
            ->willReturn(sprintf('param%d', ++$this->paramIndex));

        return $ds;
    }
}
