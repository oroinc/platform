<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Configuration;

use Oro\Bundle\DashboardBundle\Configuration\ConfigurationLoader;
use Oro\Bundle\DashboardBundle\Configuration\ConfigurationManager;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\UserBundle\Entity\User;

class ConfigurationManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $name
     * @param array  $configuration
     *
     * @dataProvider configurationProvider
     */
    public function testSaveConfiguration(
        $expectedException,
        $expectedExceptionMessage,
        $dashboardName,
        $dashboardConfiguration,
        Dashboard $repositoryDashboard = null,
        DashboardWidget $repositoryWidget = null
    ) {
        $this->setExpectedException($expectedException, $expectedExceptionMessage);

        if ($repositoryDashboard) {
            $repositoryDashboard->addWidget($repositoryWidget);
            $repositoryDashboard->setName($dashboardName);
        }
        $manager   = $this->createManager($repositoryDashboard);
        $dashboard = $manager->saveDashboardConfiguration($dashboardName, $dashboardConfiguration);

        $this->assertEquals($dashboardName, $dashboard->getName());

        foreach ($dashboard->getWidgets() as $widget) {
            /* @var DashboardWidget $widget */
            $this->assertArrayHasKey($widget->getName(), $dashboardConfiguration['widgets']);
        }
    }

    /**
     * @return array
     */
    public function configurationProvider()
    {
        return [
            'without position' => [
                'expectedException'        => '\Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException',
                'expectedExceptionMessage' => 'Position for "widget" widget should not be empty',
                'dashboardName'            => 'dashboard',
                'dashboardConfiguration'   => [
                    'widgets' => [
                        'widget' => []
                    ]
                ],
                'repositoryDashboard'      => null,
                'repositoryWidget'         => null,
            ],
            'database'         => [
                'expectedException'        => null,
                'expectedExceptionMessage' => null,
                'dashboardName'            => 'dashboard',
                'dashboardConfiguration'   => [
                    'widgets' => [
                        'widget' => [
                            'position' => 10
                        ]
                    ]
                ],
                'repositoryDashboard'      => new Dashboard(),
                'repositoryWidget'         => new DashboardWidget(),
            ],
            'reset'            => [
                'expectedException'        => null,
                'expectedExceptionMessage' => null,
                'dashboardName'            => 'dashboard',
                'dashboardConfiguration'   => [],
                'repositoryDashboard'      => null,
                'repositoryWidget'         => null,
            ],
            'normal'           => [
                'expectedException'        => null,
                'expectedExceptionMessage' => null,
                'dashboardName'            => 'dashboard',
                'dashboardConfiguration'   => [
                    'widgets' => [
                        'widget' => [
                            'position' => 10
                        ]
                    ]
                ],
                'repositoryDashboard'      => null,
                'repositoryWidget'         => null,
            ]
        ];
    }

    public function testSaveDashboardConfigurations()
    {
        $manager    = $this->createManager();
        $dashboards = $manager->saveDashboardConfigurations();

        $this->assertNotNull($dashboards);
        $this->assertEquals('dashboard', $dashboards[0]->getName());
    }

    /**
     * @param Dashboard $dashboard
     * @return ConfigurationManager
     */
    protected function createManager(Dashboard $dashboard = null)
    {
        $entityManager = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->setMethods(['getFirstMatchedUser', 'find', 'findBy', 'findAll', 'findOneBy', 'getClassName'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnValue($dashboard));

        $repository
            ->expects($this->any())
            ->method('getFirstMatchedUser')
            ->will($this->returnValue(new User()));

        $repository
            ->expects($this->at(0))
            ->method('findOneBy')
            ->with($this->equalTo(['role' => User::ROLE_ADMINISTRATOR]))
            ->will($this->returnValue(new User()));

        $entityManager
            ->expects($this->atLeastOnce())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $configProvider = $this
            ->getMockBuilder('Oro\Bundle\DashboardBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider
            ->expects($this->any())
            ->method('getWidgetConfig')
            ->will($this->returnValue([]));

        $configProvider
            ->expects($this->any())
            ->method('getDashboardConfigs')
            ->will(
                $this->returnValue(
                    [
                        'dashboard' => [
                            'widgets' => []
                        ]
                    ]
                )
            );

        return new ConfigurationManager(
            $entityManager,
            $configProvider
        );
    }
}
