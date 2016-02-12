<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Routing;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\SortableRouteCollection;

use Oro\Bundle\EntityBundle\Routing\DictionaryEntityRouteOptionsResolver;

class DictionaryEntityRouteOptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dictionaryProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityAliasResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityClassNameHelper;

    /** @var SortableRouteCollection */
    protected $routeCollection;

    /** @var RouteCollectionAccessor */
    protected $routeCollectionAccessor;

    /** @var DictionaryEntityRouteOptionsResolver */
    protected $routeOptionsResolver;

    protected function setUp()
    {
        $this->dictionaryProvider    = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityAliasResolver   = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassNameHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->routeOptionsResolver = new DictionaryEntityRouteOptionsResolver(
            $this->dictionaryProvider,
            $this->entityAliasResolver,
            $this->entityClassNameHelper
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

    public function testResolveDictionaryEntityRouteWithoutEntityPlaceholder()
    {
        $route = new Route('/route', [], [], ['group' => DictionaryEntityRouteOptionsResolver::ROUTE_GROUP]);

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);

        $this->assertEquals([], $route->getRequirements());
    }

    public function testResolve()
    {
        $route = new Route(
            '/{dictionary}/route',
            [],
            [],
            ['group' => DictionaryEntityRouteOptionsResolver::ROUTE_GROUP]
        );

        $this->routeCollection->add('first_route', new Route('/first_route'));
        $this->routeCollection->add('override_before', new Route('/sources/route'));
        $this->routeCollection->add('some_route', new Route('/some_route'));
        $this->routeCollection->add('tested_route', $route);
        $this->routeCollection->add('override_after', new Route('/statuses/route'));
        $this->routeCollection->add('last_route', new Route('/last_route'));

        $this->dictionaryProvider->expects($this->once())
            ->method('getSupportedEntityClasses')
            ->willReturn([
                'Test\Status',
                'Test\Priority',
                'Test\Source',
                'Test\Group'
            ]);

        $this->entityAliasResolver->expects($this->exactly(4))
            ->method('getPluralAlias')
            ->willReturnMap(
                [
                    ['Test\Status', 'statuses'],
                    ['Test\Priority', 'priorities'],
                    ['Test\Source', 'sources'],
                    ['Test\Group', 'groups'],
                ]
            );
        $this->entityClassNameHelper->expects($this->any())
            ->method('getUrlSafeClassName')
            ->willReturnCallback(
                function ($className) {
                    return str_replace('\\', '_', $className);
                }
            );

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);

        $this->assertEquals(
            ['dictionary' => '\w+'],
            $route->getRequirements()
        );

        $this->routeCollection->sortByPriority();
        $this->assertEquals(
            [
                'first_route',
                'some_route',
                'override_after_auto_7', // statuses
                'override_after', // statuses
                'tested_route_auto_8', // priorities
                'override_before_auto_9', // sources
                'override_before', // sources
                'tested_route_auto_10', // groups
                'tested_route',
                'last_route'
            ],
            array_keys($this->routeCollection->all())
        );

        $this->assertEquals(
            '\w+',
            $this->routeCollection->get('tested_route')->getRequirement('dictionary')
        );
        $this->assertEquals(
            'priorities',
            $this->routeCollection->get('tested_route_auto_8')->getDefault('dictionary')
        );
        $this->assertEquals(
            'groups',
            $this->routeCollection->get('tested_route_auto_10')->getDefault('dictionary')
        );
    }
}
