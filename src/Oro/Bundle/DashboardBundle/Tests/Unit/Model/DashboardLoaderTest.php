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
            $repositoryWidget->setName('widget');
            $repositoryDashboard->addWidget($repositoryWidget);
            $repositoryDashboard->setName($dashboardName);
        }
        $loader    = $this->createLoader($repositoryDashboard);
        $dashboard = $loader->saveDashboardConfiguration(
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

    public function testRemoveNonExistingWidgets()
    {
        $loader = $this->createLoaderWithQueryBuilder();
        $loader->removeNonExistingWidgets(['widget']);
    }

    /**
     * @param Dashboard $dashboard
     * @return DashboardLoader
     */
    protected function createLoader(Dashboard $dashboard = null)
    {
        $entityManager = $this->createEntityManagerMock($dashboard);

        return new DashboardLoader($entityManager);
    }

    /**
     * @param Dashboard $dashboard
     * @return DashboardLoader
     */
    protected function createLoaderWithQueryBuilder(Dashboard $dashboard = null)
    {
        $entityManager = $this->createEntityManagerMock($dashboard);

        $entityManager
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($this->createQueryBuilderMock()));

        return new DashboardLoader($entityManager);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEntityManagerMock(Dashboard $dashboard = null)
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

        return $entityManager;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createQueryBuilderMock()
    {
        $entityName = 'OroDashboardBundle:DashboardWidget';
        $entityAlias = 'w';

        $query = $this
            ->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('execute'))
            ->getMockForAbstractClass();

        $query
            ->expects($this->once())
            ->method('execute');

        $queryBuilder = $this
            ->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('delete', 'where', 'setParameter', 'getQuery'))
            ->getMock();

        $queryBuilder
            ->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($entityName), $this->equalTo($entityAlias))
            ->will($this->returnSelf());

        $queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('w.name NOT IN (:widgetNames)')
            ->will($this->returnSelf());

        $queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with($this->equalTo('widgetNames'), $this->equalTo(['widget']))
            ->will($this->returnSelf());

        $queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        return $queryBuilder;
    }
}
