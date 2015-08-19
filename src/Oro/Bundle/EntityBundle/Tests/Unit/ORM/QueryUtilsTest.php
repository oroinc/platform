<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

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
}
