<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DashboardBundle\DependencyInjection\OroDashboardExtension;
use Oro\Bundle\DashboardBundle\Tests\Unit\fixtures\SecondTestBundle\SecondTestBundle;
use Oro\Bundle\DashboardBundle\Tests\Unit\fixtures\FirstTestBundle\FirstTestBundle;

class OroDashboardExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroDashboardExtension
     */
    protected $target;

    protected function setUp()
    {
        $this->target = new OroDashboardExtension();
    }

    public function testLoad()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->once())
            ->method('getParameter')
            ->will($this->returnValue(array(new FirstTestBundle(), new SecondTestBundle())));
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $items = array(
            'create' => array(
                'label'            => 'Create opportunity',
                'route'            => 'orocrm_sales_opportunity_create',
                'acl'              => 'orocrm_sales_opportunity_create',
                'route_parameters' => array()
            ),
            'index'  => array(
                'label'            => 'List',
                'route'            => 'orocrm_sales_opportunity_index',
                'acl'              => 'orocrm_sales_opportunity_view',
                'route_parameters' => array()
            ),
            'create_without_position' => array(
                'label'            => 'Create opportunity',
                'route'            => 'orocrm_sales_opportunity_create',
                'acl'              => 'orocrm_sales_opportunity_create',
                'route_parameters' => array()
            ),
        );

        $quickLaunchpadWidget = array(
            'route'            => 'alternative_quick_lanchpad_route',
            'route_parameters' => array(
                'bundle' => 'TestBundle',
                'name'   => 'quickLaunchpad',
                'widget' => 'quick_launchpad'
            ),
            'items'            => $items
        );

        $secondQuickLaunchpadWidget = array(
            'route'            => 'second_quick_launchpad_test_route',
            'route_parameters' => array(
                'bundle' => 'SecondTestBundle',
                'name'   => 'secondQuickLaunchpad',
                'widget' => 'second_quick_launchpad'
            )
        );

        $expected = array(
            'widgets'           => array(
                'quick_launchpad'        => $quickLaunchpadWidget,
                'second_quick_launchpad' => $secondQuickLaunchpadWidget
            ),
            'dashboards'        => array(
                'main'                  => array(
                    'label'   => 'oro.dashboard.title.main',
                    'widgets' => array(
                        'second_quick_launchpad' => array(),
                        'quick_launchpad'        => array(
                            'route' => 'alternative_quick_lanchpad_route_to_dashboard_only',
                            'route_parameters'=> array('bundle' => 'TestOverrideBundle', 'name' => 'TestOverrideName')
                        )
                    )
                ),
                'alternative_dashboard' => array(
                    'label'   => 'oro.dashboard.title.alternative_dashboard',
                    'widgets' => array(
                        'quick_launchpad' => array()
                    )
                )
            ),
            'default_dashboard' => 'main1'
        );
        $definition->expects($this->once())->method('replaceArgument')->with(
            0,
            $this->callback(
                //not use equalTo because it is not check items position
                function ($actual) use ($expected) {
                    $this->assertSame($expected, $actual);
                    return true;
                }
            )
        );
        $container->expects($this->once())->method('getDefinition')->will($this->returnValue($definition));
        $this->target->load(array( array ( 'default_dashboard' => 'main1' )), $container);
    }
}
