<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Model;

use Oro\Bundle\QueryDesignerBundle\Model\GroupByHelper;

class GroupByHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider groupByDataProvider
     * @param array  $selects
     * @param string $groupBy
     * @param array  $expected
     */
    public function testGetGroupByFields($selects, $groupBy, $expected)
    {
        $helper = new GroupByHelper();
        $this->assertEquals($expected, $helper->getGroupByFields($groupBy, $selects));
    }

    /**
     * @return array
     */
    public function groupByDataProvider()
    {
        return [
            'no fields' => [
                'selects' => [],
                'groupBy' => null,
                'expected' => [],
            ],
            'group by no fields' => [
                'selects' => [],
                'groupBy' => 'alias.existing',
                'expected' => ['alias.existing'],
            ],
            'field without alias' => [
                'selects' => ['alias.field'],
                'groupBy' => 'alias.field',
                'expected' => ['alias.field'],
            ],
            'aliases and without group by' => [
                'selects' => ['alias.field', 'alias.matchedFields  as  c1', 'alias.secondMatched aS someAlias3'],
                'groupBy' => null,
                'expected' => [],
            ],
            'mixed fields and group by' => [
                'selects' => ['alias.field', 'alias.matchedFields as c1'],
                'groupBy' => 'alias.existing',
                'expected' => ['alias.existing'],
            ],
            'wrong field definition' => [
                'selects' => ['alias.matchedFields wrongas c1'],
                'groupBy' => null,
                'expected' => [],
            ],
            'with aggregate' => [
                'selects' => [
                    'MAX(t1.f0)',
                    'AvG(t10.F19) as agF1',
                    'alias.field',
                    'alias.field2',
                    'alias.matchedFields  as  c1',
                    'alias.secondMatched aS someAlias3',
                    'alias.matchedFields wrongas c1'
                ],
                'groupBy' => 'alias.field2',
                'expected' => ['alias.field2', 'alias.field', 'c1', 'someAlias3'],
            ],
            'without group by' => [
                'selects' => ['t1.f0', 't10.F19 as agF1', 'alias.matchedFields AS c1'],
                'groupBy' => null,
                'expected' => [],
            ],
        ];
    }
}
