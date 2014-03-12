<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;

use Knp\Menu\MenuItem;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\EventListener\NavigationListener;

class NavigationListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var NavigationListener */
    protected $listener;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    public function setUp()
    {
        $this->em                   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->securityFacade       = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new NavigationListener($this->em, $this->entityConfigProvider, $this->securityFacade);
    }

    public function tearDown()
    {
        unset($this->entityConfigProvider, $this->em, $this->securityFacade, $this->listener);
    }

    public function testOnNavigationConfigureNotLoggedInUser()
    {
        $factory = $this->getMock('Knp\Menu\FactoryInterface');
        $factory->expects($this->never())->method('createItem');
        $reportTab = new MenuItem('reports_tab', $factory);
        $root      = new MenuItem('root', $factory);

        $root->addChild($reportTab);

        $event = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())->method('getMenu')
            ->will($this->returnValue($root));

        $this->securityFacade->expects($this->once())->method('hasLoggedUser')
            ->will($this->returnValue(false));

        $this->em->expects($this->never())->method('getRepository');

        $this->listener->onNavigationConfigure($event);
    }

    public function testOnNavigationConfigureNoSegmentsScenario()
    {
        $factory = $this->getMock('Knp\Menu\FactoryInterface');
        $factory->expects($this->never())->method('createItem');
        $reportTab = new MenuItem('reports_tab', $factory);
        $root      = new MenuItem('root', $factory);

        $root->addChild($reportTab);

        $event = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())->method('getMenu')
            ->will($this->returnValue($root));

        $this->securityFacade->expects($this->once())->method('hasLoggedUser')
            ->will($this->returnValue(true));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('findBy')
            ->will($this->returnValue([]));

        $this->em->expects($this->once())->method('getRepository')->with('OroSegmentBundle:Segment')
            ->will($this->returnValue($repository));

        $this->listener->onNavigationConfigure($event);

        $this->assertEmpty($reportTab->getChildren(), 'should not add divider');
    }

    public function testOnNavigationConfigureDeniedScenario()
    {
        $factory = $this->getMock('Knp\Menu\FactoryInterface');
        $factory->expects($this->never())->method('createItem');
        $reportTab = new MenuItem('reports_tab', $factory);
        $root      = new MenuItem('root', $factory);

        $root->addChild($reportTab);

        $event = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())->method('getMenu')
            ->will($this->returnValue($root));

        $this->securityFacade->expects($this->once())->method('hasLoggedUser')
            ->will($this->returnValue(true));

        $testEntityName = 'Stub\Entity\Stub';
        $segment        = new Segment();
        $segment->setEntity($testEntityName);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('findBy')
            ->will($this->returnValue([$segment]));

        $this->em->expects($this->once())->method('getRepository')->with('OroSegmentBundle:Segment')
            ->will($this->returnValue($repository));

        $this->securityFacade->expects($this->atLeastOnce())->method('isGranted')
            ->with('VIEW', sprintf('entity:%s', $testEntityName))
            ->will($this->returnValue(false));

        $this->listener->onNavigationConfigure($event);

        $this->assertEmpty($reportTab->getChildren(), 'should not add divider');
    }

    /**
     * @dataProvider segmentsProvider
     *
     * @param array $segments
     * @param int   $expectedItemsCreated
     */
    public function testOnNavigationConfigureGoodScenario(array $segments, $expectedItemsCreated)
    {
        $factory   = $this->getMock('Knp\Menu\FactoryInterface');
        $reportTab = new MenuItem('reports_tab', $factory);
        $root      = new MenuItem('root', $factory);

        $root->addChild($reportTab);

        $event = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())->method('getMenu')
            ->will($this->returnValue($root));

        $this->securityFacade->expects($this->once())->method('hasLoggedUser')
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->any())->method('isGranted')->with('VIEW')
            ->will($this->returnValue(true));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('findBy')
            ->will($this->returnValue($segments));
        $this->em->expects($this->once())->method('getRepository')->with('OroSegmentBundle:Segment')
            ->will($this->returnValue($repository));

        $phpUnit = $this;
        $this->entityConfigProvider->expects($this->any())->method('getConfig')
            ->will(
                $this->returnCallback(
                    function ($entityName) use ($phpUnit) {
                        $configMock = $phpUnit->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
                        $configMock->expects($phpUnit->any())->method('get')->with('plural_label')
                            ->will($phpUnit->returnValue($entityName));

                        return $configMock;
                    }
                )
            );

        $factory->expects($this->exactly($expectedItemsCreated))->method('createItem')
            ->will(
                $this->returnCallback(
                    function ($name) use ($factory) {
                        return new MenuItem($name, $factory);
                    }
                )
            );

        $this->listener->onNavigationConfigure($event);
    }

    /**
     * @return array
     */
    public function segmentsProvider()
    {
        $stubSegment1 = new Segment();
        $stubSegment1->setEntity('Stub\Entity\Stub');
        $stubSegment1->setName(uniqid('name'));

        $stubSegment2 = new Segment();
        $stubSegment2->setEntity('Stub\Entity\Stub2');
        $stubSegment2->setName(uniqid('name'));

        $stubSegment3 = clone $stubSegment2;
        $stubSegment3->setName(uniqid('name'));

        return [
            'should add divider at least when one child exists'                          => [
                [$stubSegment1],
                3
            ],
            'should add one divider, two items for entities groups and for each segment' => [
                [$stubSegment1, $stubSegment2],
                5
            ],
            'should add entity group only once for each entity'                          => [
                [$stubSegment1, $stubSegment2, $stubSegment3],
                6
            ],
        ];
    }
}
