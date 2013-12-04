<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\QueryDesignerBundle\DependencyInjection\Compiler\ConfigurationPass;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;

class ConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $bundles    = [new TestBundle1(), new TestBundle2()];
        $managerDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $result     = null;

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->once())
            ->method('getParameter')
            ->with('kernel.bundles')
            ->will($this->returnValue($bundles));
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
                    'applicable' => [
                        ['type' => 'string'],
                        ['type' => 'text'],
                    ],
                    'type'       => 'string',
                    'query_type' => ['all']
                ],
                'filter2' => [
                    'applicable' => [
                        ['entity' => 'TestEntity', 'field' => 'TestField']
                    ],
                    'type'       => 'string',
                    'query_type' => ['all']
                ],
                'filter3' => [
                    'applicable' => [
                        ['type' => 'integer']
                    ],
                    'type'       => 'number',
                    'query_type' => ['all']
                ],
                'filter4' => [
                    'applicable' => [
                        ['type' => 'boolean']
                    ],
                    'type'       => 'boolean',
                    'query_type' => ['type1', 'type2']
                ],
            ],
            'aggregates' => [
                'aggregate1' => [
                    'applicable' => [
                        ['type' => 'integer'],
                        ['type' => 'smallint'],
                        ['type' => 'float'],
                    ],
                    'function'   => ['MIN', 'MAX', 'COUNT'],
                    'query_type' => ['type1']
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }
}
