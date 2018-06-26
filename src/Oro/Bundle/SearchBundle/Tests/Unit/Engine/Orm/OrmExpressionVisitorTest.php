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

class OrmExpressionVisitorTest extends \PHPUnit\Framework\TestCase
{
    /** @var BaseDriver|\PHPUnit\Framework\MockObject\MockObject */
    protected $driver;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
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
     * @param string $fieldName
     * @param string $expected
     */
    public function testWalkComparisonFilteringOperator($operator, $fieldName, $expected)
    {
        $index = 42;
        $type = Query::TYPE_INTEGER;

        $joinAliases = [];
        $joinField = 'search.integerFields';

        $field = strpos($fieldName, '|') !== false ? explode('|', $fieldName) : $fieldName;
        $joinAlias = str_replace('|', '_', sprintf('%sField%s_%s', $type, $fieldName, $index));

        $this->qb->expects($this->once())
            ->method('getAllAliases')
            ->willReturn($joinAliases);

        $this->driver->expects($this->once())
            ->method('getJoinAttributes')
            ->with($field, $type, $joinAliases)
            ->willReturn([$joinAlias, $index]);

        $this->driver->expects($this->once())
            ->method('getJoinField')
            ->willReturn($joinField);

        $this->qb->expects($this->once())
            ->method('leftJoin')
            ->with($joinField, $joinAlias, Join::WITH, sprintf($expected, $joinAlias, $index));

        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with(sprintf('field%s', $index), $field);

        $expression = 'RETURN EXPRESSION';

        $this->driver->expects($this->once())
            ->method('addFilteringField')
            ->with(
                $index,
                [
                    'fieldName'  => $field,
                    'condition'  => Criteria::getSearchOperatorByComparisonOperator($operator),
                    'fieldValue' => null,
                    'fieldType'  => $type
                ]
            )
            ->willReturn($expression);

        $fieldName = sprintf('%s.%s', $type, $fieldName);

        $this->assertEquals(
            $expression,
            $this->visitor->walkComparison(new Comparison($fieldName, $operator, new Value(null)))
        );
    }

    /**
     * @return array
     */
    public function filteringOperatorProvider()
    {
        return [
            'EXISTS single parameter' => [
                'operator' => SearchComparison::EXISTS,
                'fieldName' => 'testData',
                'expected' => '%s.field = :field%s'
            ],
            'NOT EXISTS single parameter' => [
                'operator' => SearchComparison::NOT_EXISTS,
                'fieldName' => 'testData',
                'expected' => '%s.field = :field%s'
            ],
            'EXISTS multi parameter' => [
                'operator' => SearchComparison::EXISTS,
                'fieldName' => 'testData1|testData2',
                'expected' => '%s.field IN (:field%s)'
            ],
            'NOT EXISTS multi parameter' => [
                'operator' => SearchComparison::NOT_EXISTS,
                'fieldName' => 'testData1|testData2',
                'expected' => '%s.field IN (:field%s)'
            ],
        ];
    }
}
