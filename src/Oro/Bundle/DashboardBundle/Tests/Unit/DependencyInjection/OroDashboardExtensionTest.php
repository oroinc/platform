<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\DependencyInjection;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Bundle\DashboardBundle\DependencyInjection\OroDashboardExtension;

class OroDashboardExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var OroDashboardExtension */
    protected $target;

    protected $bundlesState;

    protected function setUp()
    {
        $this->bundlesState = CumulativeResourceManager::getInstance()->getBundles();
        $this->target       = new OroDashboardExtension();
    }

    protected function tearDown()
    {
        CumulativeResourceManager::getInstance()->setBundles($this->bundlesState);
    }

    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad(array $bundles, array $configs, array $expectedConfiguration)
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        CumulativeResourceManager::getInstance()->setBundles($bundles);
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $definition->expects($this->once())->method('replaceArgument')->with(
            0,
            $this->callback(
                //not use equalTo because it is not check items position
                function ($actualConfiguration) use ($expectedConfiguration) {
                    $this->assertSame($expectedConfiguration, $actualConfiguration);

                    return true;
                }
            )
        );
        $container->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue($definition));
        $this->target->load($configs, $container);
    }

    public function loadDataProvider()
    {
        $firstBundle  = 'Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\FirstTestBundle';
        $secondBundle = 'Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\SecondTestBundle\SecondTestBundle';

        return [
            [
                'bundles'               => [$firstBundle, $secondBundle],
                'configs'               => [[]],
                'expectedConfiguration' => [
                    'widgets'               => [
                        'quick_launchpad'        => [
                            'route'            => 'alternative_quick_lanchpad_route',
                            'route_parameters' => [
                                'bundle' => 'TestBundle',
                                'name'   => 'quickLaunchpad',
                                'widget' => 'quick_launchpad'
                            ],
                            'items'            => [
                                'test1'  => [
                                    'label'            => 'Test1',
                                    'route'            => 'test1',
                                    'route_parameters' => [],
                                    'enabled'          => true
                                ],
                                'index'  => [
                                    'label'            => 'List',
                                    'route'            => 'orocrm_sales_opportunity_index',
                                    'acl'              => 'orocrm_sales_opportunity_view',
                                    'route_parameters' => [],
                                    'enabled'          => true
                                ],
                                'create' => [
                                    'label'            => 'Create opportunity',
                                    'route'            => 'orocrm_sales_opportunity_create',
                                    'acl'              => 'orocrm_sales_opportunity_create',
                                    'route_parameters' => [],
                                    'enabled'          => true
                                ],
                                'test2'  => [
                                    'label'            => 'Test2',
                                    'route'            => 'test2',
                                    'route_parameters' => [],
                                    'enabled'          => true
                                ]
                            ],
                            'enabled'          => true,
                            'isNew'            => false,
                            'configuration_dialog_options' => ['resizable' => false],
                            'configuration'    => [],
                            'data_items'       => [],
                        ],
                        'second_quick_launchpad' => [
                            'route'            => 'second_quick_launchpad_test_route',
                            'route_parameters' => [
                                'bundle' => 'SecondTestBundle',
                                'name'   => 'secondQuickLaunchpad',
                                'widget' => 'second_quick_launchpad'
                            ],
                            'isNew'            => true,
                            'enabled'          => true,
                            'configuration_dialog_options' => ['resizable' => false],
                            'configuration'    => [],
                            'data_items'       => [],
                        ]
                    ],
                    'dashboards'            => [
                        'main'                  => [
                            'twig' => 'OroDashboardBundle:Index:default.html.twig'
                        ],
                        'alternative_dashboard' => [
                            'twig' => 'OroDashboardBundle:Index:default.html.twig'
                        ],
                        'empty_board'           => [
                            'twig' => 'OroDashboardBundle:Index:default.html.twig'
                        ]
                    ],
                    'widgets_configuration' => []
                ]
            ]
        ];
    }
}
