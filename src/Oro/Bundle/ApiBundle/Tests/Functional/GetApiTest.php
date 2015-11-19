<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

use Oro\Bundle\ApiBundle\Routing\RestApiRouteOptionsResolver;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GetApiTest extends WebTestCase
{
    /** @var ContainerInterface */
    protected $container;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    /**
     * @dataProvider getRestRouters
     */
    public function testGetListRestRequests($entityName, $getListRoute, $getRoute)
    {
        $this->client->request(
            'GET',
            $this->getUrl($getListRoute, ['page[size]' => 1])
        );
        $response = $this->client->getResponse();
        $this->checkResponseStatus($response, $entityName, 'get list');

        $content = $this->jsonToArray($response->getContent());
        if (count($content) === 1) {
            if (array_key_exists('id', $content[0])) {
                $this->client->request(
                    'GET',
                    $this->getUrl($getRoute, ['id' => $content[0]['id']])
                );
                $response = $this->client->getResponse();
                $this->checkResponseStatus($response, $entityName, 'get');
            }
        }
    }

    /**
     * Return test data
     *
     * @return array
     */
    public function getRestRouters()
    {
        $listRoutes = [];
        $getRoutes  = [];
        $routes     = [];
        $this->initClient();
        $router           = $this->getContainer()->get('router');
        $routesCollection = $router->getRouteCollection()->getIterator();
        foreach ($routesCollection as $routeName => $route) {
            if (!$this->hasAttribute($route, RestApiRouteOptionsResolver::ENTITY_PLACEHOLDER)
                && $route->getOption('group') === RestApiRouteOptionsResolver::ROUTE_GROUP
            ) {
                if ($route->getDefault('_action') === 'get_list') {
                    $pattern = '/}\/(.*?)\.{/';
                    preg_match($pattern, $route->getPath(), $matches);
                    $entityName = $matches[1];
//                    if (in_array($entityName, ['abandonedcartcampaigns'])) {
//                        continue;
//                    }
                    $listRoutes[$entityName] = [$entityName, $routeName];
                }
                if ($route->getDefault('_action') === 'get') {
                    $pattern = '/}\/(.*?)\/{/';
                    preg_match($pattern, $route->getPath(), $matches);
                    $entityName             = $matches[1];
                    $getRoutes[$entityName] = $routeName;
                }
            }
        }

        foreach ($listRoutes as $entityName => $routeData) {
            $getRoute = '';
            if (array_key_exists($entityName, $getRoutes)) {
                $getRoute = $getRoutes[$entityName];
            }
            $routes[$entityName] = array_merge($routeData, [$getRoute]);
        }

        return $routes;
    }

    /**
     * @param Response $response
     * @param string   $entityName
     * @param string   $requestType
     */
    protected function checkResponseStatus($response, $entityName, $requestType)
    {
        try {
            $this->assertResponseStatusCodeEquals($response, 200);
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            $e = new \PHPUnit_Framework_ExpectationFailedException(
                sprintf(
                    'Wrong 200 response for "%s" request for entity: "%s". Error message: %s',
                    $requestType,
                    $entityName,
                    $e->getMessage()
                ),
                $e->getComparisonFailure()
            );
            throw $e;
        }
    }

    /**
     * Checks if a route has the given placeholder in a path.
     *
     * @param Route  $route
     * @param string $placeholder
     *
     * @return bool
     */
    protected function hasAttribute(Route $route, $placeholder)
    {
        return false !== strpos($route->getPath(), $placeholder);
    }
}
