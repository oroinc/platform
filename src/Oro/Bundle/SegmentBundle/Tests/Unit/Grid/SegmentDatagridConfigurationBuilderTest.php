<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Grid;

use Oro\Bundle\SegmentBundle\Grid\SegmentDatagridConfigurationBuilder;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;

class SegmentDatagridConfigurationBuilderTest extends SegmentDefinitionTestCase
{
    const TEST_GRID_NAME = 'test';

    public function testConfiguration()
    {
        $segment  = $this->getSegment();
        $doctrine = $this->getDoctrine([self::TEST_ENTITY => []], [self::TEST_ENTITY => [self::TEST_IDENTIFIER_NAME]]);
        $builder  = new SegmentDatagridConfigurationBuilder(
            self::TEST_GRID_NAME,
            $segment,
            $this->getFunctionProvider(),
            $doctrine
        );

        $result   = $builder->getConfiguration()->toArray();
        $expected = [
            'name'    => self::TEST_GRID_NAME,
            'columns' => ['c1' => ['label' => 'User name', 'translatable' => false, 'frontend_type' => 'string']],
            'sorters' => ['columns' => ['c1' => ['data_name' => 'c1']]],
            'filters' => ['columns' => ['c1' => ['type' => 'string', 'data_name' => 'c1', 'translatable' => false]]],
            'source'  => [
                'query'        => [
                    'select' => ['t1.userName as c1'],
                    'from'   => [['table' => self::TEST_ENTITY, 'alias' => 't1']]
                ],
                'query_config' => [
                    'filters'        => [
                        [
                            'column'     => sprintf('t1.%s', self::TEST_IDENTIFIER_NAME),
                            'filter'     => 'segment',
                            'filterData' => ['value' => self::TEST_IDENTIFIER]
                        ]
                    ],
                    'table_aliases'  => ['' => 't1'],
                    'column_aliases' => ['userName' => 'c1',]
                ],
                'type'         => 'orm',
                'acl_resource' => 'oro_segment_view',
            ],
            'options' => ['export' => true]
        ];

        $this->assertSame($expected, $result);
    }
}
