<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\QueryBuilder;

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
            $this->getSelectMock(['e.id', 'e.name']),
            $this->getSelectMock(['e.data as eData']),
            $this->getSelectMock(['someTable.field aS alias']),
            $this->getSelectMock(['someTable.field2 AS alias2']),
            $this->getSelectMock(
                [
                    "$subSelectExpression AS $subSelectAlias"
                ]
            ),
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
            $this->getParameterMock(0),
            $this->getParameterMock(1),
            $this->getParameterMock('param1'),
            $this->getParameterMock('param2'),
            $this->getParameterMock('param3'),
        ];
        $expectedParameters = [
            1 => '1_value',
            'param1' => 'param1_value',
            'param2' => 'param2_value'
        ];

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('getDql')
            ->will($this->returnValue($dql));
        $qb->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));
        $qb->expects($this->once())
            ->method('setParameters')
            ->with($expectedParameters);

        $tools = new QueryBuilderTools();
        $tools->fixUnusedParameters($qb);
    }

    /**
     * @dataProvider dqlParametersDataProvider
     * @param string $dql
     * @param string $parameter
     * @param bool $expected
     */
    public function testDqlContainsParameter($dql, $parameter, $expected)
    {
        $tools = new QueryBuilderTools();
        $this->assertEquals($expected, $tools->dqlContainsParameter($dql, $parameter));
    }

    /**
     * @return array
     */
    public function dqlParametersDataProvider()
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
     * @param string $condition
     * @param string $expected
     */
    public function testReplaceAliasesWithFields($condition, $expected)
    {
        $selects = [
            $this->getSelectMock(['e.data as eData']),
            $this->getSelectMock(['someTable.field aS alias1']),
            $this->getSelectMock(['someTable.field2 AS alias2']),
        ];

        $tools = new QueryBuilderTools($selects);
        $this->assertEquals($expected, $tools->replaceAliasesWithFields($condition));
    }

    /**
     * @return array
     */
    public function aliasConditionDataProvider()
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
            ]
        ];
    }

    /**
     * @dataProvider usedAliasesDataProvider
     * @param string|array $condition
     * @param array $expected
     */
    public function testGetUsedAliases($condition, $expected)
    {
        $selects = [
            $this->getSelectMock(['e.data as eData']),
            $this->getSelectMock(['someTable.field aS alias1']),
            $this->getSelectMock(['someTable.field2 AS alias2']),
        ];

        $tools = new QueryBuilderTools($selects);
        $this->assertEquals($expected, $tools->getUsedAliases($condition));
    }

    /**
     * @return array
     */
    public function usedAliasesDataProvider()
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
     * @param string|array $condition
     * @param array $expected
     */
    public function testGetUsedTableAliases($condition, $expected)
    {
        $selects = [
            $this->getSelectMock(['e.data as eData']),
            $this->getSelectMock(['someTable.field aS alias1']),
            $this->getSelectMock(['someTable.field2 AS alias2']),
        ];

        $tools = new QueryBuilderTools($selects);
        $this->assertEquals($expected, $tools->getUsedTableAliases($condition));
    }

    /**
     * @return array
     */
    public function usedTableAliasesDataProvider()
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
     * @param array $joins
     * @param array $aliases
     * @param array $expected
     */
    public function testGetUsedJoinAliases($joins, $aliases, $expected)
    {
        $selects = [
            $this->getSelectMock(['e.data as eData']),
            $this->getSelectMock(['t1.field aS alias1']),
            $this->getSelectMock(['t2.field2 AS alias2']),
            $this->getSelectMock(['t3.field2 AS alias3']),
        ];

        $tools = new QueryBuilderTools($selects);
        $expected = sort($expected);
        $actual = array_values($tools->getUsedJoinAliases($joins, $aliases, 'root'));
        $actual = sort($actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function joinAliasesDataProvider()
    {
        return [
            [
                [
                    'root' => [
                        $this->getJoinMock('t2', 'alias1 = :test', 't3')
                    ]
                ],
                [],
                []
            ],
            [
                [
                    'root' => [
                        $this->getJoinMock('e', 't1.id = e.id', 't1'),
                        $this->getJoinMock('t1', 't2.id = t1.id', 't2'),
                        $this->getJoinMock('t2', 'alias1 = :test', 't3')
                    ]
                ],
                ['t2'],
                ['t2', 't1', 'e']
            ],
            [
                [
                    'root' => [
                        $this->getJoinMock('e', 't1.id = e.id', 't1'),
                        $this->getJoinMock('t1', 't2.id = t3.id', 't2'),
                        $this->getJoinMock('t2', 'alias1 = :test', 't3')
                    ]
                ],
                ['t2'],
                ['t2', 't3', 't1', 'e']
            ],
            [
                [
                    'root' => [
                        $this->getJoinMock('e', 't1.id = e.id', 't1'),
                        $this->getJoinMock('t1', 't2.id = alias3', 't2'),
                        $this->getJoinMock('t2', 'alias1 = :test', 't3')
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
     * @param string $condition
     * @param array $expected
     */
    public function testGetFields($condition, $expected)
    {
        $tools = new QueryBuilderTools();
        $this->assertEquals($expected, $tools->getFields($condition));
    }

    /**
     * @return array
     */
    public function fieldsDataProvider()
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

    /**
     * @param string $join
     * @param string $condition
     * @param string $alias
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getJoinMock($join, $condition, $alias)
    {
        $joinExpr = $this->getMockBuilder('Doctrine\ORM\Query\Expr\Join')
            ->disableOriginalConstructor()
            ->getMock();
        $joinExpr->expects($this->any())
            ->method('getJoin')
            ->will($this->returnValue($join));
        $joinExpr->expects($this->any())
            ->method('getCondition')
            ->will($this->returnValue($condition));
        $joinExpr->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue($alias));

        return $joinExpr;
    }

    /**
     * @param string $name
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getParameterMock($name)
    {
        $parameter = $this->getMockBuilder('\Doctrine\ORM\Query\Parameter')
            ->disableOriginalConstructor()
            ->getMock();
        $parameter->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $parameter->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($name . '_value'));

        return $parameter;
    }

    /**
     * @param array $parts
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getSelectMock($parts)
    {
        $parts = (array)$parts;

        $select = $this->getMockBuilder('Doctrine\ORM\Query\Expr\Select')
            ->disableOriginalConstructor()
            ->getMock();
        $select->expects($this->any())
            ->method('getParts')
            ->will($this->returnValue($parts));

        return $select;
    }
}
