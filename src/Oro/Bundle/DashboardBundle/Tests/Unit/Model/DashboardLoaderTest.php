<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Configuration;

use Oro\Bundle\DashboardBundle\Model\DashboardLoader;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\UserBundle\Entity\User;

class DashboardLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string          $expectedException
     * @param string          $expectedExceptionMessage
     * @param string          $dashboardName
     * @param array           $dashboardConfiguration
     * @param Dashboard       $repositoryDashboard
     * @param DashboardWidget $repositoryWidget
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
        $dashboard = $manager->saveDashboardConfiguration(
            $dashboardName,
            $dashboardConfiguration,
            new User()
        );

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

    /**
     * @param Dashboard $dashboard
     * @return DashboardLoader
     */
    protected function createManager(Dashboard $dashboard = null)
    {
        $entityManager = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $repository
            ->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnValue($dashboard));

        $entityManager
            ->expects($this->atLeastOnce())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        return new DashboardLoader($entityManager);
    }
}
