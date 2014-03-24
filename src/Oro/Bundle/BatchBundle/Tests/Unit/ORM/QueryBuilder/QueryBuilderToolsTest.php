<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\QueryBuilder\QueryBuilderTools;

class QueryBuilderToolsTest extends \PHPUnit_Framework_TestCase
{
    public function testPrepareFieldAliases()
    {
        $selects = array(
            $this->getSelectMock(array('e.id', 'e.name')),
            $this->getSelectMock(array('e.data as eData')),
            $this->getSelectMock(array('someTable.field aS alias')),
            $this->getSelectMock(array('someTable.field2 AS alias2')),
        );

        $tools = new QueryBuilderTools();
        $tools->prepareFieldAliases($selects);
        $expected = array(
            'eData' => 'e.data',
            'alias' => 'someTable.field',
            'alias2' => 'someTable.field2',
        );
        $this->assertEquals($expected, $tools->getFieldAliases());
        $this->assertEquals('e.data', $tools->getFieldByAlias('eData'));
        $this->assertNull($tools->getFieldByAlias('unknown'));

        $tools->resetFieldAliases();
        $this->assertNull($tools->getFieldByAlias('eData'));
    }

    public function testFixUnusedParameters()
    {
        $dql = 'SELECT a.name FROM Some:Other as a WHERE a.name = :param1
            AND a.name != :param2 AND a.status = ?1';
        $parameters = array(
            $this->getParameterMock(0),
            $this->getParameterMock(1),
            $this->getParameterMock('param1'),
            $this->getParameterMock('param2'),
            $this->getParameterMock('param3'),
        );
        $expectedParameters = array(
            1 => '1_value',
            'param1' => 'param1_value',
            'param2' => 'param2_value'
        );

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

    public function dqlParametersDataProvider()
    {
        $dql = 'SELECT a.name FROM Some:Other as a WHERE a.name = :param1
            AND a.name != :param2 AND a.status = ?1';

        return array(
            array($dql, 'param1', true),
            array($dql, 'param5', false),
            array($dql, 'param11', false),
            array($dql, 0, false),
            array($dql, 1, true),
        );
    }

    /**
     * @dataProvider aliasConditionDataProvider
     * @param string $condition
     * @param string $expected
     */
    public function testReplaceAliasesWithFields($condition, $expected)
    {
        $selects = array(
            $this->getSelectMock(array('e.data as eData')),
            $this->getSelectMock(array('someTable.field aS alias1')),
            $this->getSelectMock(array('someTable.field2 AS alias2')),
        );

        $tools = new QueryBuilderTools($selects);
        $this->assertEquals($expected, $tools->replaceAliasesWithFields($condition));
    }

    public function aliasConditionDataProvider()
    {
        return array(
            array(
                'alias1',
                'someTable.field'
            ),
            array(
                ' alias1',
                'someTable.field'
            ),
            array(
                'alias1 ',
                'someTable.field'
            ),
            array(
                'alias1 = :alias1',
                'someTable.field = :alias1'
            ),
            array(
                'alias1 > 123 AND UPPER(alias2)=:alias1 OR eData=alias1',
                'someTable.field > 123 AND UPPER(someTable.field2)=:alias1 OR e.data=someTable.field'
            )
        );
    }

    /**
     * @dataProvider usedAliasesDataProvider
     * @param string|array $condition
     * @param array $expected
     */
    public function testGetUsedAliases($condition, $expected)
    {
        $selects = array(
            $this->getSelectMock(array('e.data as eData')),
            $this->getSelectMock(array('someTable.field aS alias1')),
            $this->getSelectMock(array('someTable.field2 AS alias2')),
        );

        $tools = new QueryBuilderTools($selects);
        $this->assertEquals($expected, $tools->getUsedAliases($condition));
    }

    public function usedAliasesDataProvider()
    {
        return array(
            array(
                'eData = ?',
                array('eData')
            ),
            array('', array()),
            array(
                array('UPPER(alias1)=UPPER(eData)', 'UPPER("str") = eData AND alias1 = :alias2'),
                array('eData', 'alias1')
            )
        );
    }

    /**
     * @dataProvider usedTableAliasesDataProvider
     * @param string|array $condition
     * @param array $expected
     */
    public function testGetUsedTableAliases($condition, $expected)
    {
        $selects = array(
            $this->getSelectMock(array('e.data as eData')),
            $this->getSelectMock(array('someTable.field aS alias1')),
            $this->getSelectMock(array('someTable.field2 AS alias2')),
        );

        $tools = new QueryBuilderTools($selects);
        $this->assertEquals($expected, $tools->getUsedTableAliases($condition));
    }

    public function usedTableAliasesDataProvider()
    {
        return array(
            array(
                'eData = :alias1',
                array('e')
            ),
            array(
                'someTable.field = :eData',
                array('someTable')
            ),
            array(
                array('someTable.field = ?', 'eData = ?'),
                array('someTable', 'e')
            ),
        );
    }

    /**
     * @dataProvider joinAliasesDataProvider
     * @param array $joins
     * @param array $aliases
     * @param array $expected
     */
    public function testGetUsedJoinAliases($joins, $aliases, $expected)
    {
        $selects = array(
            $this->getSelectMock(array('e.data as eData')),
            $this->getSelectMock(array('t1.field aS alias1')),
            $this->getSelectMock(array('t2.field2 AS alias2')),
            $this->getSelectMock(array('t3.field2 AS alias3')),
        );

        $tools = new QueryBuilderTools($selects);
        $this->assertEquals($expected, array_values($tools->getUsedJoinAliases($joins, $aliases, 'root')));
    }

    public function joinAliasesDataProvider()
    {
        return array(
            array(
                array(
                    'root' => array(
                        $this->getJoinMock('t2', 'alias1 = :test', 't3')
                    )
                ),
                array(),
                array()
            ),
            array(
                array(
                    'root' => array(
                        $this->getJoinMock('e', 't1.id = e.id', 't1'),
                        $this->getJoinMock('t1', 't2.id = t1.id', 't2'),
                        $this->getJoinMock('t2', 'alias1 = :test', 't3')
                    )
                ),
                array('t2'),
                array('t2', 't1')
            ),
            array(
                array(
                    'root' => array(
                        $this->getJoinMock('e', 't1.id = e.id', 't1'),
                        $this->getJoinMock('t1', 't2.id = t3.id', 't2'),
                        $this->getJoinMock('t2', 'alias1 = :test', 't3')
                    )
                ),
                array('t2'),
                array('t2', 't1', 't3')
            ),
            array(
                array(
                    'root' => array(
                        $this->getJoinMock('e', 't1.id = e.id', 't1'),
                        $this->getJoinMock('t1', 't2.id = alias3', 't2'),
                        $this->getJoinMock('t2', 'alias1 = :test', 't3')
                    )
                ),
                array('t2'),
                array('t2', 't1', 't3')
            )
        );
    }

    /**
     * @dataProvider havingDataProvider
     * @param string $condition
     * @param string $expected
     */
    public function testFixHavingAliases($condition, $expected)
    {
        $selects = array(
            $this->getSelectMock(array('e.data as eData')),
            $this->getSelectMock(array('someTable.field aS alias1')),
            $this->getSelectMock(array('someTable.field2 AS alias2')),
        );

        $tools = new QueryBuilderTools($selects);
        $this->assertEquals($expected, $tools->fixHavingAliases($condition));
    }

    public function havingDataProvider()
    {
        return array(
            array(
                'eData LIKE :test',
                'e.data LIKE :test'
            ),
            array(
                'eData NOT LIKE :test',
                'e.data NOT LIKE :test'
            ),
            array(
                'eData IS NULL',
                'e.data IS NULL'
            ),
            array(
                'eData IS NOT NULL',
                'e.data IS NOT NULL'
            ),
            array(
                'eData = :test1 AND eData NOT LIKE :test',
                'eData = :test1 AND e.data NOT LIKE :test'
            ),
            array(
                'eData = :test1 AND eData LIKE :test',
                'eData = :test1 AND e.data LIKE :test'
            ),
            array(
                'eData = :test1 AND eData IS NULL',
                'eData = :test1 AND e.data IS NULL'
            ),
            array(
                'eData = :test1 AND eData IS NOT NULL',
                'eData = :test1 AND e.data IS NOT NULL'
            )
        );
    }

    /**
     * @dataProvider fieldsDataProvider
     * @param string $condition
     * @param array $expected
     */
    public function getFields($condition, $expected)
    {
        $tools = new QueryBuilderTools();
        $this->assertEquals($expected, $tools->getFields($condition));
    }

    public function fieldsDataProvider()
    {
        return array(
            array('2 < 3', array()),
            array(
                't1.field = t2.field AND t1.field IS NOT NULL',
                array('t1.field, t2.field')
            )
        );
    }

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

    protected function getSelectMock($parts)
    {
        $parts = (array) $parts;

        $select = $this->getMockBuilder('Doctrine\ORM\Query\Expr\Select')
            ->disableOriginalConstructor()
            ->getMock();
        $select->expects($this->any())
            ->method('getParts')
            ->will($this->returnValue($parts));

        return $select;
    }
}
