<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Api\Processor\Routing;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\EnhancedRouteCollection;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\ApiDoc\RestRouteOptionsResolver;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\UserBundle\Api\Model\UserProfile;
use Oro\Bundle\UserBundle\Api\Routing\UserProfileRestRouteOptionsResolver;

class UserProfileRestRouteOptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $docViewDetector;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var UserProfileRestRouteOptionsResolver */
    protected $userProfileRestRouteOptionsResolver;

    protected function setUp()
    {
        $this->docViewDetector = $this->getMockBuilder(RestDocViewDetector::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueNormalizer = $this->getMockBuilder(ValueNormalizer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userProfileRestRouteOptionsResolver = new UserProfileRestRouteOptionsResolver(
            $this->docViewDetector,
            $this->valueNormalizer
        );
    }

    public function testResolveForRouteFromNotRestApiGroup()
    {
        $route = new Route('/api/{entity}/{id}');

        $routes = new EnhancedRouteCollection();
        $routes->add('test', $route);
        $accessor = new RouteCollectionAccessor($routes);

        $this->userProfileRestRouteOptionsResolver->resolve($route, $accessor);

        self::assertCount(1, $routes);
    }

    public function testResolveForRouteFromRestApiGroupButNotForGetAction()
    {
        $route = new Route('/api/{entity}/{id}');
        $route->setOption('group', RestRouteOptionsResolver::ROUTE_GROUP);

        $routes = new EnhancedRouteCollection();
        $routes->add('test', $route);
        $accessor = new RouteCollectionAccessor($routes);

        $this->userProfileRestRouteOptionsResolver->resolve($route, $accessor);

        self::assertCount(1, $routes);
    }

    public function testResolveForNotDefaultGetActionRestApiRoute()
    {
        $route = new Route('/api/{entity}/{id}', ['_action' => ApiActions::GET]);
        $route->setOption('group', RestRouteOptionsResolver::ROUTE_GROUP);

        $routes = new EnhancedRouteCollection();
        $routes->add('test', $route);
        $accessor = new RouteCollectionAccessor($routes);

        $this->userProfileRestRouteOptionsResolver->resolve($route, $accessor);

        self::assertCount(1, $routes);
    }

    public function testResolveForDefaultGetActionRestApiRouteWhenExceptionOccurredDuringGetEntityType()
    {
        $route = new Route('/api/{entity}/{id}', ['_action' => ApiActions::GET]);
        $route->setOption('group', RestRouteOptionsResolver::ROUTE_GROUP);

        $routes = new EnhancedRouteCollection();
        $routes->add(UserProfileRestRouteOptionsResolver::GET_ROUTE_NAME, $route);
        $accessor = new RouteCollectionAccessor($routes);

        $requestType = new RequestType([RequestType::REST]);
        $this->docViewDetector->expects(self::once())
            ->method('getRequestType')
            ->willReturn($requestType);
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with(UserProfile::class, DataType::ENTITY_TYPE, self::identicalTo($requestType))
            ->willThrowException(new \Exception('some error'));

        $this->userProfileRestRouteOptionsResolver->resolve($route, $accessor);

        self::assertCount(1, $routes);
    }

    public function testResolveForDefaultGetActionRestApiRoute()
    {
        $route = new Route('/api/{entity}/{id}', ['_action' => ApiActions::GET], ['entity' => '\w+']);
        $route->setOption('group', RestRouteOptionsResolver::ROUTE_GROUP);

        $routes = new EnhancedRouteCollection();
        $routes->add(UserProfileRestRouteOptionsResolver::GET_ROUTE_NAME, $route);
        $accessor = new RouteCollectionAccessor($routes);

        $entityType = 'userprofile';
        $requestType = new RequestType([RequestType::REST]);
        $this->docViewDetector->expects(self::once())
            ->method('getRequestType')
            ->willReturn($requestType);
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with(UserProfile::class, DataType::ENTITY_TYPE, self::identicalTo($requestType))
            ->willReturn($entityType);

        $this->userProfileRestRouteOptionsResolver->resolve($route, $accessor);

        $expectedRoute = new Route(
            '/api/' . $entityType,
            ['_action' => ApiActions::GET, 'entity' => $entityType]
        );
        $expectedRoute->setOption('group', RestRouteOptionsResolver::ROUTE_GROUP);

        self::assertCount(2, $routes);
        $allRoutes = $routes->all();
        $userProfileRoute = reset($allRoutes);
        self::assertEquals($expectedRoute, $userProfileRoute);
        self::assertSame(
            $userProfileRoute,
            $routes->get(UserProfileRestRouteOptionsResolver::USER_PROFILE_ROUTE_NAME)
        );
    }
}
