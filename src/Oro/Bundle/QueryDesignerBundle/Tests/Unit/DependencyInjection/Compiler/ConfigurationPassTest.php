<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\QueryDesignerBundle\DependencyInjection\Compiler\ConfigurationPass;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;

class ConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcess()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1->getName() => get_class($bundle1), $bundle2->getName() => get_class($bundle2)]);

        $managerDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $result     = null;

        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(ConfigurationPass::MANAGER_SERVICE_ID)
            ->will($this->returnValue(true));
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(ConfigurationPass::MANAGER_SERVICE_ID)
            ->will($this->returnValue($managerDef));
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ConfigurationPass::TAG_NAME)
            ->will(
                $this->returnValue(
                    [
                        'string_filter'  => [
                            ['type' => 'string']
                        ],
                        'integer_filter' => [
                            ['type' => 'integer']
                        ],
                        'number_filter'  => [
                            ['type' => 'number']
                        ],
                        'boolean_filter' => [
                            ['type' => 'boolean']
                        ],
                    ]
                )
            );
        $managerDef->expects($this->once())
            ->method('replaceArgument')
            ->will(
                $this->returnCallback(
                    function ($index, $argument) use (&$result) {
                        $result = $argument;
                    }
                )
            );

        $compiler = new ConfigurationPass();
        $compiler->process($container);

        $expected = [
            'filters'    => [
                'filter1' => [
                    'applicable'     => [
                        ['type' => 'string'],
                        ['type' => 'text'],
                    ],
                    'type'           => 'string',
                    'template_theme' => 'embedded',
                    'query_type'     => ['all']
                ],
                'filter2' => [
                    'applicable'     => [
                        ['entity' => 'TestEntity', 'field' => 'TestField']
                    ],
                    'type'           => 'string',
                    'template_theme' => 'embedded',
                    'query_type'     => ['all']
                ],
                'filter3' => [
                    'applicable'     => [
                        ['type' => 'integer']
                    ],
                    'type'           => 'number',
                    'template_theme' => 'embedded',
                    'query_type'     => ['all']
                ],
                'filter4' => [
                    'applicable'     => [
                        ['type' => 'boolean']
                    ],
                    'type'           => 'boolean',
                    'template_theme' => 'embedded',
                    'query_type'     => ['type1', 'type2']
                ],
                'filter5' => [
                    'applicable'         => [
                        ['type' => 'other']
                    ],
                    'type'           => 'string',
                    'options'        => ['option1' => true, 'option2' => 'val2'],
                    'init_module'    => 'module1',
                    'template_theme' => 'embedded',
                    'query_type'     => ['all']
                ],
            ],
            'grouping'   => [
                'exclude' => [
                    ['type' => 'text'],
                    ['type' => 'array']
                ]
            ],
            'converters' => [
                'converter1' => [
                    'applicable' => [
                        ['type' => 'string'],
                        ['type' => 'text'],
                    ],
                    'functions'  => [
                        [
                            'name'        => 'Func1',
                            'expr'        => 'FUNC1($column)',
                            'return_type' => 'string',
                            'name_label'  => 'oro.query_designer.converters.converter1.Func1.name',
                            'hint_label'  => 'oro.query_designer.converters.converter1.Func1.hint',
                        ],
                        [
                            'name'       => 'Func2',
                            'expr'       => 'FUNC2($column)',
                            'name_label' => 'oro.query_designer.converters.converter1.Func2.name',
                            'hint_label' => 'oro.query_designer.converters.converter1.Func2.hint',
                        ],
                    ],
                    'query_type' => ['type1']
                ]
            ],
            'aggregates' => [
                'aggregate1' => [
                    'applicable' => [
                        ['type' => 'integer'],
                        ['type' => 'smallint'],
                        ['type' => 'float'],
                    ],
                    'functions'  => [
                        [
                            'name'       => 'Min',
                            'expr'       => 'MIN($column)',
                            'name_label' => 'oro.query_designer.aggregates.aggregate1.Min.name',
                            'hint_label' => 'oro.query_designer.aggregates.aggregate1.Min.hint',
                        ],
                        [
                            'name'       => 'Max',
                            'expr'       => 'MAX($column)',
                            'name_label' => 'oro.query_designer.aggregates.aggregate1.Max.name',
                            'hint_label' => 'oro.query_designer.aggregates.aggregate1.Max.hint',
                        ],
                        [
                            'name'        => 'Count',
                            'expr'        => 'COUNT($column)',
                            'return_type' => 'integer',
                            'name_label'  => 'oro.query_designer.aggregates.aggregate1.Count.name',
                            'hint_label'  => 'oro.query_designer.aggregates.aggregate1.Count.hint',
                        ],
                    ],
                    'query_type' => ['type1']
                ]
            ],
        ];

        $this->assertEquals($expected, $result);
    }
}
