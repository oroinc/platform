<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\EntityPaginationBundle\Datagrid\EntityPaginationExtension;

class EntityPaginationExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityPaginationExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension  = new EntityPaginationExtension();
        $this->extension->setParameters(new ParameterBag());
    }

    /**
     * @param $input
     * @param $result
     *
     * @dataProvider isApplicableProvider
     */
    public function testIsApplicable($input, $result)
    {
        $this->assertEquals(
            $this->extension->isApplicable(
                DatagridConfiguration::create($input)
            ),
            $result
        );
    }

    /**
     * @param $input
     * @param $result
     *
     * @dataProvider processConfigsProvider
     */
    public function testProcessConfigs($input, $result)
    {
        $config = DatagridConfiguration::create($input);
        $this->extension->processConfigs($config);
        $resultConfig = $config->offsetGetByPath(EntityPaginationExtension::ENTITY_PAGINATION_PATH);
        $this->assertEquals(
            $resultConfig,
            $result
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Entity pagination is not boolean
     */
    public function testProcessException()
    {
        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'entity_pagination' => 100,
                ]
            ]
        );
        $this->extension->processConfigs($config);
    }

    public function isApplicableProvider()
    {
        return [
            [
                'input' => [
                    'options' => [
                        'entity_pagination' => true,
                    ],
                    'source'  => [
                        'type' => 'orm'
                    ]
                ],
                'result' => true
            ],
            [
                'input' => [
                    'options' => [
                        'entity_pagination' => false,
                    ],
                    'source'  => [
                        'type' => 'orm'
                    ]
                ],
                'result' => true
            ],
            [
                'input' => [
                    'source' => [
                        'type' => 'orm'
                    ]
                ],
                'result' => false
            ],
            [
                'input' => [
                    'options' => [
                        'entity_pagination' => true,
                    ],
                ],
                'result' => false
            ]
        ];
    }

    public function processConfigsProvider()
    {
        return [
            [
                'input' => [
                    'name' => 'test_grid_pagination',
                    'source' => [
                        'type' => 'orm'
                    ],
                    'options' => [
                        'entity_pagination' => true
                    ]
                ],
                'result' => true
            ],
            [
                'input' => [
                    'name' => 'test_grid_without_pagination',
                    'source' => [
                        'type' => 'orm'
                    ],
                    'options' => [
                        'entity_pagination' => false
                    ]
                ],
                'result' => false
            ],
            [
                'input' => [
                    'name' => 'test_grid_without_pagination_option',
                    'source' => [
                        'type' => 'orm'
                    ]
                ],
                'result' => false
            ],
        ];
    }
}
