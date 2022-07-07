<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\QueryBuilder;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\QueryBuilderTools;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QueryBuilderToolsTest extends \PHPUnit\Framework\TestCase
{
    public function testPrepareFieldAliases()
    {
        $subSelectExpression = '(SELECT sub_select_alias.id as subselect_id, sub_select_alias.name' .
            ' as subselect_name FROM FAKE\ENTITY as sub_select_alias)';
        $subSelectAlias = 'subSelectAlias';

        $selects = [
            $this->getSelect(['e.id', 'e.name']),
            $this->getSelect(['e.data as eData']),
            $this->getSelect(['someTable.field aS alias']),
            $this->getSelect(['someTable.field2 AS alias2']),
            $this->getSelect(["$subSelectExpression AS $subSelectAlias"]),
        ];

        $tools = new QueryBuilderTools($selects);
        $expected = [
            'eData' => 'e.data',
            'alias' => 'someTable.field',
            'alias2' => 'someTable.field2',
            $subSelectAlias => $subSelectExpression
        ];
        $this->assertEquals($expected, $tools->getFieldAliases());
        $this->assertEquals('e.data', $tools->getFieldByAlias('eData'));
        $this->assertEquals($subSelectExpression, $tools->getFieldByAlias($subSelectAlias));
        $this->assertNull($tools->getFieldByAlias('unknown'));

        $tools->resetFieldAliases();
        $this->assertNull($tools->getFieldByAlias('eData'));
    }

    public function testFixUnusedParameters()
    {
        $dql = 'SELECT a.name FROM Some:Other as a WHERE a.name = :param1
            AND a.name != :param2 AND a.status = ?1';
        $parameters = [
            $this->getParameter(0),
            $this->getParameter(1),
            $this->getParameter('param1'),
            $this->getParameter('param2'),
            $this->getParameter('param3'),
        ];
        $expectedParameters = [
            1 => '1_value',
            'param1' => 'param1_value',
            'param2' => 'param2_value'
        ];

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getDql')
            ->willReturn($dql);
        $qb->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);
        $qb->expects($this->once())
            ->method('setParameters')
            ->with($expectedParameters);

        $tools = new QueryBuilderTools();
        $tools->fixUnusedParameters($qb);
    }

    /**
     * @dataProvider dqlParametersDataProvider
     */
    public function testDqlContainsParameter(string $dql, string|int $parameter, bool $expected)
    {
        $tools = new QueryBuilderTools();
        $this->assertEquals($expected, $tools->dqlContainsParameter($dql, $parameter));
    }

    public function dqlParametersDataProvider(): array
    {
        $dql = 'SELECT a.name FROM Some:Other as a WHERE a.name = :param1
            AND a.name != :param2 AND a.status = ?1';

        return [
            [$dql, 'param1', true],
            [$dql, 'param5', false],
            [$dql, 'param11', false],
            [$dql, 0, false],
            [$dql, 1, true],
        ];
    }

    /**
     * @dataProvider aliasConditionDataProvider
     */
    public function testReplaceAliasesWithFields(string $condition, string $expected)
    {
        $selects = [
            $this->getSelect(['e.data as eData']),
            $this->getSelect(['someTable.field aS alias1']),
            $this->getSelect(['someTable.field2 AS alias2']),
        ];

        $tools = new QueryBuilderTools($selects);
        $this->assertEquals($expected, $tools->replaceAliasesWithFields($condition));
    }

    public function aliasConditionDataProvider(): array
    {
        return [
            [
                'alias1',
                'someTable.field'
            ],
            [
                'table.alias1',
                'table.alias1'
            ],
            [
                ' alias1',
                'someTable.field'
            ],
            [
                'alias1 ',
                'someTable.field'
            ],
            [
                'alias1 = :alias1',
                'someTable.field = :alias1'
            ],
            [
                'alias1 > 123 AND UPPER(alias2)=:alias1 OR eData=alias1',
                'someTable.field > 123 AND UPPER(someTable.field2)=:alias1 OR e.data=someTable.field'
            ],
            [
                'table.alias1 > 123 AND UPPER(table.alias2)=:alias1 OR eData=table.alias1',
                'table.alias1 > 123 AND UPPER(table.alias2)=:alias1 OR e.data=table.alias1'
            ],
            [
                "CUSTOM_FUNCTION(table.data_holder, 'alias1') > 123",
                "CUSTOM_FUNCTION(table.data_holder, 'alias1') > 123"
            ],
            [
                'CUSTOM_FUNCTION(table.data_holder, "alias1") > alias2',
                'CUSTOM_FUNCTION(table.data_holder, "alias1") > someTable.field2'
            ]
        ];
    }

    /**
     * @dataProvider usedAliasesDataProvider
     */
    public function testGetUsedAliases(string|array $condition, array $expected)
    {
        $selects = [
            $this->getSelect(['e.data as eData']),
            $this->getSelect(['someTable.field aS alias1']),
            $this->getSelect(['someTable.field2 AS alias2']),
        ];

        $tools = new QueryBuilderTools($selects);
        $this->assertEquals($expected, $tools->getUsedAliases($condition));
    }

    public function usedAliasesDataProvider(): array
    {
        return [
            [
                'eData = ?',
                ['eData']
            ],
            ['', []],
            [
                ['UPPER(alias1)=UPPER(eData)', 'UPPER("str") = eData AND alias1 = :alias2'],
                ['eData', 'alias1']
            ]
        ];
    }

    /**
     * @dataProvider usedTableAliasesDataProvider
     */
    public function testGetUsedTableAliases(string|array $condition, array $expected)
    {
        $selects = [
            $this->getSelect(['e.data as eData']),
            $this->getSelect(['someTable.field aS alias1']),
            $this->getSelect(['someTable.field2 AS alias2']),
        ];

        $tools = new QueryBuilderTools($selects);
        $this->assertEquals($expected, $tools->getUsedTableAliases($condition));
    }

    public function usedTableAliasesDataProvider(): array
    {
        return [
            [
                'eData = :alias1',
                ['e']
            ],
            [
                'someTable.field = :eData',
                ['someTable']
            ],
            [
                ['someTable.field = ?', 'eData = ?'],
                ['someTable', 'e']
            ],
        ];
    }

    /**
     * @dataProvider joinAliasesDataProvider
     */
    public function testGetUsedJoinAliases(array $joins, array $aliases, array $expected)
    {
        $selects = [
            $this->getSelect(['e.data as eData']),
            $this->getSelect(['t1.field aS alias1']),
            $this->getSelect(['t2.field2 AS alias2']),
            $this->getSelect(['t3.field2 AS alias3']),
        ];

        $tools = new QueryBuilderTools($selects);
        $expected = sort($expected);
        $actual = array_values($tools->getUsedJoinAliases($joins, $aliases, 'root'));
        $actual = sort($actual);
        $this->assertEquals($expected, $actual);
    }

    public function joinAliasesDataProvider(): array
    {
        return [
            [
                [
                    'root' => [
                        $this->getJoin('t2', 'alias1 = :test', 't3')
                    ]
                ],
                [],
                []
            ],
            [
                [
                    'root' => [
                        $this->getJoin('e', 't1.id = e.id', 't1'),
                        $this->getJoin('t1', 't2.id = t1.id', 't2'),
                        $this->getJoin('t2', 'alias1 = :test', 't3')
                    ]
                ],
                ['t2'],
                ['t2', 't1', 'e']
            ],
            [
                [
                    'root' => [
                        $this->getJoin('e', 't1.id = e.id', 't1'),
                        $this->getJoin('t1', 't2.id = t3.id', 't2'),
                        $this->getJoin('t2', 'alias1 = :test', 't3')
                    ]
                ],
                ['t2'],
                ['t2', 't3', 't1', 'e']
            ],
            [
                [
                    'root' => [
                        $this->getJoin('e', 't1.id = e.id', 't1'),
                        $this->getJoin('t1', 't2.id = alias3', 't2'),
                        $this->getJoin('t2', 'alias1 = :test', 't3')
                    ]
                ],
                ['t2'],
                ['t2', 't3', 't1', 'e']
            ]
        ];
    }

    public function testGetFieldsWithoutAggregateFunctions()
    {
        $tools = new QueryBuilderTools();

        $condition = '(DATE(l.createdAt) >= :param1 AND MIN(l.createdAt) <= :param2) AND ' .
            '(COUNT(l.id) >= :param3 AND MAX(l.id) <= :param4) AND '.
            '(TIME(l.updatedAt) = :param5 OR AVG(l.id) > :param6 OR DATE(l.createdAt) >= :param7)';

        $this->assertEquals(
            [
                'DATE(l.createdAt)',
                'TIME(l.updatedAt)'
            ],
            array_values($tools->getFieldsWithoutAggregateFunctions($condition))
        );
    }

    public function testGetFieldsWithoutAggregateFunctionsForSeveralNestedFunctions()
    {
        $tools = new QueryBuilderTools();

        $condition = 'DATE(CONVERT_TZ(l.createdAt, \'+00:00\', \'+03:00\')) >= :param1 ' .
            'AND MIN(l.updatedAt) <= :param2';

        $this->assertEquals(
            [
                'DATE(CONVERT_TZ(l.createdAt, \'+00:00\', \'+03:00\'))'
            ],
            array_values($tools->getFieldsWithoutAggregateFunctions($condition))
        );
    }

    public function testGetFieldsWithoutAggregateFunctionsForFunctionInMiddleOfMatchedExpression()
    {
        $tools = new QueryBuilderTools();

        $condition = 'DATE_ADD(CURRENT_TIME(), INTERVAL 4 MONTH) >= :param1 ' .
            'AND MIN(l.updatedAt) <= :param2';

        $this->assertEquals(
            [
                'DATE_ADD(CURRENT_TIME(), INTERVAL 4 MONTH)'
            ],
            array_values($tools->getFieldsWithoutAggregateFunctions($condition))
        );
    }

    /**
     * @dataProvider fieldsDataProvider
     */
    public function testGetFields(string $condition, array $expected)
    {
        $tools = new QueryBuilderTools();
        $this->assertEquals($expected, $tools->getFields($condition));
    }

    public function fieldsDataProvider(): array
    {
        return [
            ['2 < 3', []],
            [
                't1.field = t2.field AND t1.field IS NOT NULL',
                [
                    't1.field',
                    't2.field'
                ]
            ]
        ];
    }

    private function getJoin(string $join, string $condition, string $alias): Join
    {
        $joinExpr = $this->createMock(Join::class);
        $joinExpr->expects($this->any())
            ->method('getJoin')
            ->willReturn($join);
        $joinExpr->expects($this->any())
            ->method('getCondition')
            ->willReturn($condition);
        $joinExpr->expects($this->any())
            ->method('getAlias')
            ->willReturn($alias);

        return $joinExpr;
    }

    private function getParameter(string $name): Parameter
    {
        $parameter = $this->createMock(Parameter::class);
        $parameter->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $parameter->expects($this->any())
            ->method('getValue')
            ->willReturn($name . '_value');

        return $parameter;
    }

    private function getSelect(array $parts): Select
    {
        $select = $this->createMock(Select::class);
        $select->expects($this->any())
            ->method('getParts')
            ->willReturn($parts);

        return $select;
    }
}
