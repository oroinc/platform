<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\DashboardBundle\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_DASHBOARD_NAME = 'default_dashboard';
    const DASHBOARD_NAME = 'dashboard';

    /**
     * @dataProvider dashboardProvider
     */
    public function testGetDefaultDashboardName($config, $securityFacade)
    {
        $dashboard = new Manager($config, $securityFacade);
        $this->assertEquals(self::DEFAULT_DASHBOARD_NAME, $dashboard->getDefaultDashboardName());
    }

    /**
     * @dataProvider dashboardProvider
     */

    public function testGetDashboards($config, $securityFacade)
    {
        $dashboard = new Manager($config, $securityFacade);
        $this->assertEquals(array(self::DEFAULT_DASHBOARD_NAME, self::DASHBOARD_NAME), $dashboard->getDashboards());
    }

    /**
     * @dataProvider dashboardProvider
     */
    public function getDashboard($config, $securityFacade)
    {
        $dashboard = new Manager($config, $securityFacade);
        $this->assertEquals(self::DASHBOARD_NAME, $dashboard->getDashboard(self::DASHBOARD_NAME));
    }

    /**
     * @dataProvider widgetsProvider
     */
    public function testGetDashboardWidgets($config, $securityFacade, $expectedResult)
    {

        $dashboard = new Manager($config, $securityFacade);
        $this->assertEquals($expectedResult, $dashboard->getDashboardWidgets('default_dashboard'));
    }

    /**
     * @dataProvider attributesProvider
     */
    public function testGetWidgetAttributes($config, $securityFacade, $expectedResult)
    {
        $dashboard = new Manager($config, $securityFacade);
        $this->assertEquals($expectedResult, $dashboard->getWidgetAttributes('widget'));
    }

    /**
     * @dataProvider itemsProvider
     */
    public function testGetWidgetItems($config, $securityFacade, $expectedResult)
    {
        $dashboard = new Manager($config, $securityFacade);
        $this->assertEquals($expectedResult, $dashboard->getWidgetItems('widget'));
    }

    /**
     * @dataProvider attributesTwigProvider
     */
    public function testGetWidgetAttributesForTwig($config, $securityFacade, $expectedResult)
    {
        $dashboard = new Manager($config, $securityFacade);
        $this->assertEquals($expectedResult, $dashboard->getWidgetAttributesForTwig('widget'));
    }

    public function widgetsProvider()
    {
        $securityFacade = $this->getMock(
            'Oro\Bundle\SecurityBundle\SecurityFacade',
            array('isGranted'),
            array(),
            '',
            false
        );
        $securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnArgument(0));

        return array(
            array(
                'config' => array('dashboards' => array(
                    'default_dashboard' => array(
                        'label' => self::DEFAULT_DASHBOARD_NAME,
                        'widgets' => array(
                            'widget' => array(
                                'acl' => true,
                                'items' => 'acl'
                            )
                        )

                    )
                )),
                'securityFacade' => $securityFacade,
                'expectedResult' => array('widget' => array())
            ),
            array(
                'config' => array('dashboards' => array(
                    'default_dashboard' => array(
                        'label' => self::DEFAULT_DASHBOARD_NAME,
                        'widgets' => array(
                            'widget' => array(
                                'items' => 'acl'
                            )
                        )

                    )
                )),
                'securityFacade' => $securityFacade,
                'expectedResult' => array('widget' => array())
            ),
            array(
                'config' => array('dashboards' => array(
                    'default_dashboard' => array(
                        'label' => self::DEFAULT_DASHBOARD_NAME,
                        'widgets' => array(
                            'widget' => array(
                                'acl' => false,
                                'items' => 'acl'
                            )
                        )

                    )
                )),
                'securityFacade' => $securityFacade,
                'expectedResult' => array()
            ),
            array(
                'config' => array('dashboards' => array(
                    'default_dashboard' => array(
                        'label' => self::DEFAULT_DASHBOARD_NAME,
                        'widgets' => array(
                            'widget' => array(
                                'items' => 'acl'
                            )
                        )
                    )
                ),
                'widgets' => array(
                    'widget' => array(
                        'acl' => true,
                        'items' => 'acl'
                    )
                )),
                'securityFacade' => $securityFacade,
                'expectedResult' => array('widget' => array())
            ),
        );
    }

    public function dashboardProvider()
    {
        $securityFacade = $this->getMock('Oro\Bundle\SecurityBundle\SecurityFacade', array(), array(), '', false);
        $config = array(
            'default_dashboard' => self::DEFAULT_DASHBOARD_NAME,
            'dashboards' => array(
                array(
                    'label' => self::DEFAULT_DASHBOARD_NAME,
                ),
                array(
                    'label' => self::DASHBOARD_NAME,
                ),
            )
        );

        return array(
            array(
                'config' => $config,
                'securityFacade' => $securityFacade
            )
        );
    }

    public function attributesProvider()
    {
        $securityFacade = $this->getMock('Oro\Bundle\SecurityBundle\SecurityFacade', array(), array(), '', false);
        $config = array(
            'widgets' => array(
                'widget' => array(
                    'route' => true,
                    'route_parameters' => 'route_parameters',
                    'acl' => 'acl',
                    'items' => 'items',
                    'attributes' => 'attributes',
                    'attributes-twig' => 'attributes-twig',
                )
            )
        );

        return array(
            array(
                'config' => $config,
                'securityFacade' => $securityFacade,
                'expectedResult' => array('attributes' => 'attributes', 'attributes-twig' => 'attributes-twig')
            )
        );
    }

    public function attributesTwigProvider()
    {
        $securityFacade = $this->getMock('Oro\Bundle\SecurityBundle\SecurityFacade', array(), array(), '', false);
        $config = array(
            'widgets' => array(
                'widget' => array(
                    'route' => true,
                    'route_parameters' => 'route_parameters',
                    'acl' => 'acl',
                    'items' => 'items',
                    'attributes' => 'attributes',
                    'attributes-twig' => 'attributes-twig',
                )
            )
        );

        return array(
            array(
                'config' => $config,
                'securityFacade' => $securityFacade,
                'expectedResult' => array(
                    'widgetName' => 'widget',
                    'widgetAttributes' => 'attributes',
                    'widgetAttributesTwig' => 'attributes-twig'
                )
            )
        );
    }

    public function itemsProvider()
    {
        $securityFacade = $this->getMock(
            'Oro\Bundle\SecurityBundle\SecurityFacade',
            array('isGranted'),
            array(),
            '',
            false
        );
        $securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnArgument(0));

        return array(
            array(
                'config' => array(
                    'widgets' => array(
                        'widget' => array(
                            'route' => true,
                            'route_parameters' => 'route_parameters',
                            'acl' => 'acl',
                            'attributes' => 'attributes'
                        )
                    )
                ),
                'securityFacade' => $securityFacade,
                'expectedResult' => array()
            ),
            array(
                'config' => array(
                    'widgets' => array(
                        'widget' => array(
                            'route' => true,
                            'route_parameters' => 'route_parameters',
                            'acl' => 'acl',
                            'attributes' => 'attributes',
                            'items' => array(
                                'item' => array(
                                    'acl' => true
                                )
                            )

                        )
                    )
                ),
                'securityFacade' => $securityFacade,
                'expectedResult' => array('item' => array())
            ),
            array(
                'config' => array(
                    'widgets' => array(
                        'widget' => array(
                            'route' => true,
                            'route_parameters' => 'route_parameters',
                            'acl' => 'acl',
                            'attributes' => 'attributes',
                            'items' => array(
                                'item' => array(
                                    'acl' => false
                                )
                            )

                        )
                    )
                ),
                'securityFacade' => $securityFacade,
                'expectedResult' => array()
            )
        );
    }
}
