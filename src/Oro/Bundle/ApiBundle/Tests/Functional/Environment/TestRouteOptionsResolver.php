<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Oro\Bundle\ApiBundle\ApiDoc\RestRouteOptionsResolver;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\Routing\Route;

class TestRouteOptionsResolver implements RouteOptionsResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if ('rest_api' !== $route->getOption(RestRouteOptionsResolver::GROUP_OPTION)) {
            return;
        }
        if ('oro_rest_api_item' !== $routes->getName($route)) {
            return;
        }

        $this->addTestRoutes($routes);
    }

    /**
     * @param RouteCollectionAccessor $routes
     */
    private function addTestRoutes(RouteCollectionAccessor $routes)
    {
        $routes->append(
            'oro_rest_tests_override_path',
            new Route(
                '/api/testapicurrentdepartment',
                [
                    '_controller' => 'OroApiBundle:RestApi:item',
                    'entity'      => 'testapicurrentdepartments'
                ],
                [],
                [
                    'group'         => 'rest_api',
                    'override_path' => '/api/testapicurrentdepartments/{id}'
                ]
            )
        );
        $routes->append(
            'oro_rest_tests_override_path_subresource',
            new Route(
                '/api/testapicurrentdepartment/{association}',
                [
                    '_controller' => 'OroApiBundle:RestApi:subresource',
                    'entity'      => 'testapicurrentdepartments'
                ],
                [],
                [
                    'group'         => 'rest_api',
                    'override_path' => '/api/testapicurrentdepartments/{id}/{association}'
                ]
            )
        );
        $routes->append(
            'oro_rest_tests_override_path_relationship',
            new Route(
                '/api/testapicurrentdepartment/relationships/{association}',
                [
                    '_controller' => 'OroApiBundle:RestApi:relationship',
                    'entity'      => 'testapicurrentdepartments'
                ],
                [],
                [
                    'group'         => 'rest_api',
                    'override_path' => '/api/testapicurrentdepartments/{id}/relationships/{association}'
                ]
            )
        );

        $routes->append(
            'oro_rest_tests_resource_without_identifier',
            new Route(
                '/api/testapiresourcewithoutidentifier',
                [
                    '_controller' => 'OroApiBundle:RestApi:itemWithoutId',
                    'entity'      => 'testapiresourcewithoutidentifier'
                ],
                [],
                [
                    'group' => 'rest_api'
                ]
            )
        );
    }
}
