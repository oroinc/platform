<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Api\Processor\ApiDoc;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\EnhancedRouteCollection;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Bundle\ApiBundle\ApiDoc\RestRouteOptionsResolver;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\UserBundle\Api\Model\UserProfile;
use Oro\Bundle\UserBundle\Api\ApiDoc\UserProfileRestRouteOptionsResolver;

class UserProfileRestRouteOptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var UserProfileRestRouteOptionsResolver */
    protected $userProfileRestRouteOptionsResolver;

    protected function setUp()
    {
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->userProfileRestRouteOptionsResolver = new UserProfileRestRouteOptionsResolver(
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

    public function testResolveForUserProfileGetActionRestApiRoute()
    {
        $route = new Route('/api/{entity}/{id}', ['_action' => ApiActions::GET, 'entity' => 'userprofile']);
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
        $userProfileRoute = new Route(
            '/api/userprofile/{id}',
            ['_action' => ApiActions::GET, 'entity' => 'userprofile']
        );

        $routes = new EnhancedRouteCollection();
        $routes->add('test', $route);
        $routes->add('user_profile_route', $userProfileRoute);
        $accessor = new RouteCollectionAccessor($routes);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with(UserProfile::class, DataType::ENTITY_TYPE, new RequestType([RequestType::REST]))
            ->willThrowException(new \Exception('some error'));

        $this->userProfileRestRouteOptionsResolver->resolve($route, $accessor);

        self::assertCount(2, $routes);
    }

    public function testResolveForDefaultGetActionRestApiRouteWhenUserProfileRouteWithIdDoesNotExist()
    {
        $route = new Route('/api/{entity}/{id}', ['_action' => ApiActions::GET]);
        $route->setOption('group', RestRouteOptionsResolver::ROUTE_GROUP);

        $routes = new EnhancedRouteCollection();
        $routes->add('test', $route);
        $accessor = new RouteCollectionAccessor($routes);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with(UserProfile::class, DataType::ENTITY_TYPE, new RequestType([RequestType::REST]))
            ->willReturn('userprofile');

        $this->userProfileRestRouteOptionsResolver->resolve($route, $accessor);

        self::assertCount(1, $routes);
    }

    public function testResolveForDefaultGetActionRestApiRouteWhenUserProfileRouteWithIdExists()
    {
        $route = new Route('/api/{entity}/{id}', ['_action' => ApiActions::GET]);
        $route->setOption('group', RestRouteOptionsResolver::ROUTE_GROUP);
        $userProfileRoute = new Route(
            '/api/userprofile/{id}',
            ['_action' => ApiActions::GET, 'entity' => 'userprofile']
        );

        $routes = new EnhancedRouteCollection();
        $routes->add('test', $route);
        $routes->add('user_profile_route', $userProfileRoute);
        $accessor = new RouteCollectionAccessor($routes);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with(UserProfile::class, DataType::ENTITY_TYPE, new RequestType([RequestType::REST]))
            ->willReturn('userprofile');

        $this->userProfileRestRouteOptionsResolver->resolve($route, $accessor);

        self::assertCount(1, $routes);
    }
}
