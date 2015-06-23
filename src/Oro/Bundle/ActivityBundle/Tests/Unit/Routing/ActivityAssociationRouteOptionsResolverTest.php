<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Routing;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\SortableRouteCollection;

use Oro\Bundle\ActivityBundle\Routing\ActivityAssociationRouteOptionsResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class ActivityAssociationRouteOptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $groupingConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityAliasResolver;

    /** @var SortableRouteCollection */
    protected $routeCollection;

    /** @var RouteCollectionAccessor */
    protected $routeCollectionAccessor;

    /** @var ActivityAssociationRouteOptionsResolver */
    protected $routeOptionsResolver;

    protected function setUp()
    {
        $this->groupingConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->setMethods(['getConfigs'])
            ->getMock();
        $this->entityAliasResolver    = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->routeOptionsResolver = new ActivityAssociationRouteOptionsResolver(
            $this->groupingConfigProvider,
            $this->entityAliasResolver
        );

        $this->routeCollection         = new SortableRouteCollection();
        $this->routeCollectionAccessor = new RouteCollectionAccessor($this->routeCollection);
    }

    public function testResolveUnsupportedRoute()
    {
        $route = new Route('/route');

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);

        $this->assertEquals([], $route->getRequirements());
    }

    public function testResolveActivityRelationRouteWithoutEntityPlaceholder()
    {
        $route = new Route('/route', [], [], ['group' => 'activity_association']);

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);

        $this->assertEquals([], $route->getRequirements());
    }

    public function testResolve()
    {
        $route = new Route('/{activity}/route', [], [], ['group' => 'activity_association']);

        $this->routeCollection->add('first_route', new Route('/first_route'));
        $this->routeCollection->add(
            'override1',
            new Route('/notes/route')
        );
        $this->routeCollection->add('some_route', new Route('/some_route'));
        $this->routeCollection->add('tested_route', $route);
        $this->routeCollection->add(
            'override2',
            new Route('/emails/route')
        );
        $this->routeCollection->add('last_route', new Route('/last_route'));

        $config1 = new Config(new EntityConfigId('grouping', 'Test\Email'));
        $config1->set('groups', ['activity']);
        $config2 = new Config(new EntityConfigId('grouping', 'Test\Call'));
        $config2->set('groups', ['test', 'activity']);
        $config3 = new Config(new EntityConfigId('grouping', 'Test\Message'));
        $config4 = new Config(new EntityConfigId('grouping', 'Test\Note'));
        $config4->set('groups', ['activity']);

        $this->groupingConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with(null, false)
            ->willReturn([$config1, $config2, $config3, $config4]);

        $this->entityAliasResolver->expects($this->exactly(3))
            ->method('getPluralAlias')
            ->willReturnMap(
                [
                    ['Test\Email', 'emails'],
                    ['Test\Call', 'calls'],
                    ['Test\Note', 'notes']
                ]
            );

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);

        $this->assertEquals(['activity' => 'emails|calls|notes'], $route->getRequirements());

        $this->routeCollection->sortByPriority();
        $this->assertEquals(
            [
                'first_route',
                'some_route',
                'override2',
                'tested_route_auto_7',
                'override1',
                'tested_route',
                'last_route'
            ],
            array_keys($this->routeCollection->all())
        );

        $this->assertEquals(
            'emails|calls|notes',
            $this->routeCollection->get('tested_route')->getRequirement('activity')
        );
        $this->assertEquals(
            'calls',
            $this->routeCollection->get('tested_route_auto_7')->getDefault('activity')
        );
    }
}
