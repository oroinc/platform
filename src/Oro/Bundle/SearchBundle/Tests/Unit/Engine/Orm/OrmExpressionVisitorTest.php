<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine\Orm;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SearchBundle\Engine\Orm\BaseDriver;
use Oro\Bundle\SearchBundle\Engine\Orm\OrmExpressionVisitor;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison as SearchComparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class OrmExpressionVisitorTest extends \PHPUnit_Framework_TestCase
{
    /** @var BaseDriver|\PHPUnit_Framework_MockObject_MockObject */
    protected $driver;

    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $qb;

    /** @var OrmExpressionVisitor */
    protected $visitor;

    protected function setUp()
    {
        $this->driver = $this->createMock(BaseDriver::class);
        $this->qb = $this->createMock(QueryBuilder::class);

        $this->visitor = new OrmExpressionVisitor($this->driver, $this->qb);
    }

    /**
     * @dataProvider filteringOperatorProvider
     *
     * @param string $operator
     */
    public function testWalkComparisontWalkComparisonFilteringOperator($operator)
    {
        $index = 42;
        $type = Query::TYPE_INTEGER;
        $fieldName = 'testData';

        $joinAliases = [];
        $joinField = 'search.integerFields';

        $field = sprintf('%s.%s', $type, $fieldName);
        $joinAlias = sprintf('%sField%s_%s', $type, $fieldName, $index);

        $comparison = new Comparison($field, $operator, new Value(null));

        $this->qb->expects($this->once())
            ->method('getAllAliases')
            ->willReturn($joinAliases);

        $this->driver->expects($this->once())
            ->method('getJoinAttributes')
            ->with($fieldName, $type, $joinAliases)
            ->willReturn([$joinAlias, $index]);

        $this->driver->expects($this->once())
            ->method('getJoinField')
            ->willReturn($joinField);

        $this->qb->expects($this->once())
            ->method('leftJoin')
            ->with($joinField, $joinAlias, Join::WITH, sprintf('%s.field = :field%s', $joinAlias, $index));

        $expected = 'EXPECTED EXPRESSION';

        $this->driver->expects($this->once())
            ->method('addFilteringField')
            ->with(
                $this->qb,
                $index,
                [
                    'fieldName'  => $fieldName,
                    'condition'  => Criteria::getSearchOperatorByComparisonOperator($operator),
                    'fieldValue' => null,
                    'fieldType'  => $type
                ]
            )
            ->willReturn($expected);

        $this->assertEquals($expected, $this->visitor->walkComparison($comparison));
    }

    /**
     * @return array
     */
    public function filteringOperatorProvider()
    {
        return [
            SearchComparison::EXISTS => [
                'operator' => SearchComparison::EXISTS,
            ],
            SearchComparison::NOT_EXISTS => [
                'operator' => SearchComparison::NOT_EXISTS,
            ],
        ];
    }
}
