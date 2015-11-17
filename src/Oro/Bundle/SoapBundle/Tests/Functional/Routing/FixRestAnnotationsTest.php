<?php

namespace Oro\Bundle\SoapBundle\Tests\Functional\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FixRestAnnotationsTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testRestApiRoutes()
    {
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');

        $brokenRoutes = [];
        /** @var Route $route */
        foreach ($router->getRouteCollection() as $routeName => $route) {
            if (false !== strpos($routeName, '_api_') && 0 !== strpos($route->getPath(), '/api/')) {
                $brokenRoutes[$routeName] = $route->getPath();
            }
        }
        if (!empty($brokenRoutes)) {
            $message = "All REST API routes should start with '/api/'. Broken routes:\n";
            foreach ($brokenRoutes as $routeName => $routePath) {
                $message .= sprintf("%s: %s\n", $routeName, $routePath);
            }
            $this->fail($message);
        }
    }
}
