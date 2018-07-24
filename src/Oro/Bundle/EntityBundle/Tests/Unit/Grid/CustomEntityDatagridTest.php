<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\EntityBundle\Grid\CustomEntityDatagrid;

class CustomEntityDatagridTest extends \PHPUnit\Framework\TestCase
{
    public function testInitialize()
    {
        $config = DatagridConfiguration::create([]);
        new CustomEntityDatagrid('test', $config, new ParameterBag(['class_name' => 'Test\Entity']));
        $this->assertEquals(
            [
                'source' => [
                    'query' => [
                        'from' => [
                            ['table' => 'Test\Entity']
                        ]
                    ]
                ]
            ],
            $config->toArray()
        );
    }
}
