<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\QueryUtils;

class QueryUtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider concatExprProvider
     *
     * @param string[] $parts
     * @param string   $expectedExpr
     */
    public function testBuildConcatExpr($parts, $expectedExpr)
    {
        $this->assertEquals($expectedExpr, QueryUtils::buildConcatExpr($parts));
    }

    public function concatExprProvider()
    {
        return [
            [[], ''],
            [[''], ''],
            [['a.field1'], 'a.field1'],
            [['a.field1', 'a.field2'], 'CONCAT(a.field1, a.field2)'],
            [['a.field1', 'a.field2', 'a.field3'], 'CONCAT(a.field1, CONCAT(a.field2, a.field3))'],
            [['a.field1', '\' \'', 'a.field3'], 'CONCAT(a.field1, CONCAT(\' \', a.field3))'],
        ];
    }

    /**
     * @dataProvider getSelectExprByAliasProvider
     *
     * @param QueryBuilder $qb
     * @param string       $alias
     * @param string       $expectedExpr
     */
    public function testGetSelectExprByAlias($qb, $alias, $expectedExpr)
    {
        $this->assertEquals($expectedExpr, QueryUtils::getSelectExprByAlias($qb, $alias));
    }

    public function getSelectExprByAliasProvider()
    {
        return [
            [
                $this->getQueryBuilder()->select('e'),
                'test',
                null
            ],
            [
                $this->getQueryBuilder()->select('e.id as id'),
                'id',
                'e.id'
            ],
            [
                $this->getQueryBuilder()->select('e.id as id, e.name AS name1'),
                'id',
                'e.id'
            ],
            [
                $this->getQueryBuilder()->select('e.id as id, e.name AS name1'),
                'name1',
                'e.name'
            ],
            [
                $this->getQueryBuilder()->select('e.id as id, e.name AS name1')->addSelect('e.lbl AS name2'),
                'name2',
                'e.lbl'
            ],
            [
                $this->getQueryBuilder()->select('e.id as id, CONCAT(e.name1, e.name2) AS name'),
                'name',
                'CONCAT(e.name1, e.name2)'
            ],
        ];
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return new QueryBuilder($this->getMock('Doctrine\ORM\EntityManagerInterface'));
    }
}
