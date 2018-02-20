<?php

namespace Oro\Bundle\SoapBundle\Tests\Functional\Routing;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

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
            if ($this->checkRoute($routeName, $route->getPath())) {
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

    /**
     * @param string $routeName
     * @param string $routePath
     *
     * @return bool
     */
    protected function checkRoute($routeName, $routePath)
    {
        $oroDefaultPrefix = $this->getUrl('oro_default');
        /**
         * CRM only mode
         */
        if ($oroDefaultPrefix === '/') {
            return (false !== strpos($routeName, '_api_') && 0 !== strpos($routePath, '/api/'));
        }

        /**
         * Integration mode (CRM + COMMERCE)
         */
        return
            false !== strpos($routeName, '_api_') &&
            0 !== strpos($routePath, '/api/') && 0 !== strpos($routePath, $oroDefaultPrefix . 'api/');
    }
}
