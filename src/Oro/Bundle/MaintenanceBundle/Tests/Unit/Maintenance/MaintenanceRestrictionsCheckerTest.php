<?php

namespace Oro\Bundle\MaintenanceBundle\Tests\Unit\Maintenance;

use Oro\Bundle\MaintenanceBundle\Maintenance\MaintenanceRestrictionsChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MaintenanceRestrictionsCheckerTest extends TestCase
{
    /**
     * @dataProvider allowedIpDataProvider
     */
    public function testIsAllowedIp(
        $ips,
        $expectedResult
    ): void {
        $request = Request::create('http://test.com/foo?bar=baz');

        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            null,
            $ips,
            [],
            [],
            [],
            false,
            $request
        );

        self::assertEquals($expectedResult, $maintenanceRestrictionsChecker->isAllowedIp());
    }

    public function allowedIpDataProvider(): array
    {
        return [
            'allowedIpDataSet' => [
                'ips' => ['127.0.0.1'],
                'expectedResult' => true
            ],
            'notAllowedIpDataSet' => [
                'ips' => ['192.168.0.1'],
                'expectedResult' => false
            ],
            'notSetIpDataSet' => [
                'ips' => [],
                'expectedResult' => false
            ]
        ];
    }

    /**
     * @dataProvider routeFilterDataProvider
     */
    public function testRouteFilter(
        $route,
        $expectedResult
    ): void {
        $request = Request::create('');
        $request->attributes->set('_route', $route);

        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            $route,
            [],
            [],
            [],
            [],
            false,
            $request
        );

        self::assertEquals($expectedResult, $maintenanceRestrictionsChecker->isAllowedRoute());
    }

    public function routeFilterDataProvider(): array
    {
        return [
            'debug, common route' => [
                'route' => 'route_1',
                'expectedResult' => true
            ],
            'debug, debug route' => [
                'route' => '_route_started_with_underscore',
                'expectedResult' => true
            ],
            'debug, no route' => [
                'route' => 'route_1',
                'expectedResult' => true
            ],
            'not debug, common route' => [
                'route' => 'route_1',
                'expectedResult' => true
            ],
            'not debug, debug route' => [
                'route' => '_route_started_with_underscore',
                'expectedResult' => true
            ],
            'not debug, no route' => [
                'route' => 'route_1',
                'expectedResult' => true
            ]
        ];
    }

    /**
     * @dataProvider pathFilterDataProvider
     */
    public function testPathFilter(
        $path,
        $expectedResult
    ): void {
        $request = Request::create('http://test.com/foo?bar=baz');

        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            $path,
            null,
            null,
            [],
            [],
            [],
            [],
            false,
            $request
        );

        self::assertEquals($expectedResult, $maintenanceRestrictionsChecker->isAllowedPath());
    }

    public function pathFilterDataProvider(): array
    {
        return [
            'without path' => [
                'path' => null,
                'expectedResult' => false,
            ],
            'empty path' => [
                'path' => '',
                'expectedResult' => false,
            ],
            'non matching path' => [
                'path' => '/bar',
                'expectedResult' => false,
            ],
            'matching path' => [
                'path' => '/foo',
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider hostFilterDataProvider
     */
    public function testHostFilter(?string $host, bool $expectedResult): void
    {
        $request = Request::create('http://test.com/foo?bar=baz');

        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            $host,
            null,
            [],
            [],
            [],
            [],
            false,
            $request
        );

        self::assertEquals($expectedResult, $maintenanceRestrictionsChecker->isAllowedHost());
    }

    public function hostFilterDataProvider(): array
    {
        return [
            'without host' => [
                'host' => null,
                'expected' => false,
            ],
            'empty host' => [
                'host' => '',
                'expected' => false,
            ],
            'non matching host' => [
                'host' => 'www.google.com',
                'expected' => false,
            ],
            'matching host' => [
                'host' => 'test.com',
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider queryFilterDataProvider
     */
    public function testQueryFilter(Request $request, ?array $query, bool $expectedResult): void
    {
        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            null,
            [],
            $query,
            [],
            [],
            false,
            $request
        );

        self::assertEquals($expectedResult, $maintenanceRestrictionsChecker->isAllowedQuery());
    }

    public function queryFilterDataProvider(): array
    {
        $request = Request::create('http://test.com/foo?bar=baz');
        $postRequest = Request::create('http://test.com/foo?bar=baz', 'POST');

        return [
            'empty query' => [
                'request' => $request,
                'query' => [],
                'expected' => false,
            ],
            'non matching query' => [
                'request' => $request,
                'query' => ['some' => 'attribute'],
                'expected' => false,
            ],
            'matching query' => [
                'request' => $request,
                'query' => ['bar' => 'baz'],
                'expected' => true,
            ],
            'matching post query' => [
                'request' => $postRequest,
                'query' => ['bar' => 'baz'],
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider cookieFilterDataProvider
     */
    public function testCookieFilter(?array $cookies, bool $expectedResult): void
    {
        $request = Request::create('http://test.com/foo', 'GET', [], ['bar' => 'baz']);

        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            '/barfoo',
            'www.google.com',
            null,
            ['8.8.4.4'],
            ['bar' => 'baz'],
            $cookies,
            [],
            false,
            $request
        );

        self::assertEquals($expectedResult, $maintenanceRestrictionsChecker->isAllowedCookie());
    }

    public function cookieFilterDataProvider(): array
    {
        return [
            'empty cookies' => [
                'cookies' => [],
                'expectedResult' => false,
            ],
            'non matching cookie (array)' => [
                'cookies' => ['some' => 'attribute'],
                'expectedResult' => false,
            ],
            'non matching cookie (list)' => [
                'cookies' => ['attribute'],
                'expectedResult' => false,
            ],
            'matching cookie' => [
                'cookies' => ['bar' => 'baz'],
                'expectedResult' => true,
            ],
        ];
    }
    private function getMaintenanceRestrictionsChecker(
        ?string $path = null,
        ?string $host = null,
        ?string $route = null,
        array $ips = [],
        array $query = [],
        array $cookie = [],
        array $attributes = [],
        bool $debug = false,
        ?Request $request = null,
    ): MaintenanceRestrictionsChecker {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new MaintenanceRestrictionsChecker(
            $requestStack,
            $path,
            $host,
            $route,
            $ips,
            $query,
            $cookie,
            $attributes,
            $debug
        );
    }
}
