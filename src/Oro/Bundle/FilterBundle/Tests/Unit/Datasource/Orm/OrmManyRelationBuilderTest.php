<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmManyRelationBuilder;
use Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm\Fixtures\Entity as Stub;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class OrmManyRelationBuilderTest extends OrmTestCase
{
    const NS = 'Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm\Fixtures\Entity\\';

    /** @var EntityManagerMock */
    protected $em;

    /** @var OrmManyRelationBuilder */
    protected $builder;

    /** @var int */
    protected $paramIndex;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm\Fixtures\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Stub' => 'Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm\Fixtures\Entity'
            ]
        );

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $this->paramIndex = 0;
        $this->builder    = new OrmManyRelationBuilder($doctrine);
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
                $this->createMock('Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface')
            )
        );
    }

    /**
     * @dataProvider inverseProvider
     */
    public function testBuildComparisonExprSimple($inverse)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id')
            ->from('Stub:TestOrder', 'o');

        $ds = $this->getFilterDatasourceAdapter($qb);
        $expr = $this->builder->buildComparisonExpr($ds, 'o.products', 'param1', 'test', $inverse);

        $qb->where($expr);
        $result = $qb->getDQL();

        $operator = $inverse ? 'NOT IN' : 'IN';
        $this->assertEquals(
            'SELECT o.id FROM Stub:TestOrder o'
            . ' WHERE o ' . $operator . '('
            . 'SELECT filter_param1'
            . ' FROM Stub:TestOrder filter_param1'
            . ' INNER JOIN filter_param1.products filter_param1_rel'
            . ' WHERE filter_param1_rel IN(:param1))',
            $result
        );
    }

    /**
     * @dataProvider inverseProvider
     */
    public function testBuildNullValueExprSimple($inverse)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id')
            ->from('Stub:TestOrder', 'o');

        $ds = $this->getFilterDatasourceAdapter($qb);
        $expr = $this->builder->buildNullValueExpr($ds, 'o.products', 'test', $inverse);

        $qb->where($expr);
        $result = $qb->getDQL();

        $operator = $inverse ? 'IS NOT' : 'IS';
        $this->assertEquals(
            'SELECT o.id FROM Stub:TestOrder o'
            . ' WHERE o IN('
            . 'SELECT null_filter_test'
            . ' FROM Stub:TestOrder null_filter_test'
            . ' LEFT JOIN null_filter_test.products null_filter_test_rel'
            . ' WHERE null_filter_test_rel ' . $operator . ' NULL)',
            $result
        );
    }

    /**
     * @dataProvider inverseProvider
     */
    public function testBuildComparisonExprWithSimpleJoin($inverse)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id, p1.id')
            ->from('Stub:TestOrder', 'o')
            ->leftJoin('o.products', 'p');

        $ds = $this->getFilterDatasourceAdapter($qb);
        $expr = $this->builder->buildComparisonExpr($ds, 'p.notes', 'param1', 'test', $inverse);

        $qb->where($expr);
        $result = $qb->getDQL();

        $operator = $inverse ? 'NOT IN' : 'IN';
        $this->assertEquals(
            'SELECT o.id, p1.id FROM Stub:TestOrder o'
            . ' LEFT JOIN o.products p'
            . ' WHERE p ' . $operator . '('
            . 'SELECT filter_param1'
            . ' FROM ' . self::NS . 'TestProduct filter_param1'
            . ' INNER JOIN filter_param1.notes filter_param1_rel'
            . ' WHERE filter_param1_rel IN(:param1))',
            $result
        );
    }

    /**
     * @dataProvider inverseProvider
     */
    public function testBuildComparisonExprWithUnidirectionalJoin($inverse)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id, p1.id')
            ->from('Stub:TestOrder', 'o')
            ->leftJoin('o.products', 'p')
            ->leftJoin('Stub:TestComment', 'c', 'WITH', 'c.products = p AND p.id = 5')
            ->leftJoin('c.products', 'p1');

        $ds = $this->getFilterDatasourceAdapter($qb);
        $expr = $this->builder->buildComparisonExpr($ds, 'p1.orders', 'param1', 'test', $inverse);

        $qb->where($expr);
        $result = $qb->getDQL();

        $operator = $inverse ? 'NOT IN' : 'IN';
        $this->assertEquals(
            'SELECT o.id, p1.id FROM Stub:TestOrder o'
            . ' LEFT JOIN o.products p'
            . ' LEFT JOIN Stub:TestComment c WITH c.products = p AND p.id = 5'
            . ' LEFT JOIN c.products p1'
            . ' WHERE p1 ' . $operator . '('
            . 'SELECT filter_param1'
            . ' FROM ' . self::NS . 'TestProduct filter_param1'
            . ' INNER JOIN filter_param1.orders filter_param1_rel'
            . ' WHERE filter_param1_rel IN(:param1))',
            $result
        );
    }

    public function inverseProvider()
    {
        return [
            [false],
            [true],
        ];
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return OrmFilterDatasourceAdapter
     */
    protected function getFilterDatasourceAdapter(QueryBuilder $qb)
    {
        /** @var OrmFilterDatasourceAdapter|\PHPUnit\Framework\MockObject\MockObject $ds */
        $ds = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter')
            ->setMethods(['generateParameterName'])
            ->setConstructorArgs([$qb])
            ->getMock();
        $ds->expects($this->any())
            ->method('generateParameterName')
            ->will($this->returnValue(sprintf('param%d', ++$this->paramIndex)));

        return $ds;
    }
}
