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
    private function getMaintenanceRestrictionsChecker(
        ?string $path,
        ?string $host,
        ?string $route,
        ?array $ips,
        ?array $query,
        ?array $cookie,
        ?array $attributes,
        ?bool $debug,
        ?Request $request,
    ): MaintenanceRestrictionsChecker {
        $requestStack = new RequestStack();
        if (null !== $request) {
            $requestStack->push($request);
        }

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

    public function testIsAllowedIpWhenNoCurrentRequest(): void
    {
        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            null,
            ['127.0.0.1'],
            [],
            [],
            [],
            false,
            null
        );

        self::assertFalse($maintenanceRestrictionsChecker->isAllowedIp());
    }

    /**
     * @dataProvider isAllowedIpDataProvider
     */
    public function testIsAllowedIp(?array $ips, bool $expectedResult): void
    {
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

    public function isAllowedIpDataProvider(): array
    {
        return [
            'null ips' => [
                'ips' => null,
                'expectedResult' => false
            ],
            'empty ips' => [
                'ips' => [],
                'expectedResult' => false
            ],
            'matching ips' => [
                'ips' => ['192.168.0.1', '127.0.0.1', '192.168.0.2'],
                'expectedResult' => true
            ],
            'not matching ips' => [
                'ips' => ['192.168.0.1', '192.168.0.2'],
                'expectedResult' => false
            ]
        ];
    }

    public function testIsAllowedRouteWhenNoCurrentRequest(): void
    {
        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            '\w*route\w*',
            [],
            [],
            [],
            [],
            false,
            null
        );

        self::assertFalse($maintenanceRestrictionsChecker->isAllowedRoute());
    }

    public function testIsAllowedRouteWhenNullRoute(): void
    {
        $request = Request::create('');
        $request->attributes->set('_route', 'route_1');

        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            null,
            [],
            [],
            [],
            [],
            false,
            $request
        );

        self::assertFalse($maintenanceRestrictionsChecker->isAllowedRoute());
    }

    public function testIsAllowedRouteWhenEmptyRoute(): void
    {
        $request = Request::create('');
        $request->attributes->set('_route', 'route_1');

        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            '',
            [],
            [],
            [],
            [],
            false,
            $request
        );

        self::assertFalse($maintenanceRestrictionsChecker->isAllowedRoute());
    }

    /**
     * @dataProvider isAllowedRouteDataProvider
     */
    public function testIsAllowedRoute(bool $debug, ?string $requestRoute, bool $expectedResult): void
    {
        $request = Request::create('');
        if (null !== $requestRoute) {
            $request->attributes->set('_route', $requestRoute);
        }

        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            '\w*route\w*',
            [],
            [],
            [],
            [],
            $debug,
            $request
        );

        self::assertEquals($expectedResult, $maintenanceRestrictionsChecker->isAllowedRoute());
    }

    public function isAllowedRouteDataProvider(): array
    {
        return [
            'no route' => [
                'debug' => false,
                'requestRoute' => null,
                'expectedResult' => false
            ],
            'no route, debug' => [
                'debug' => true,
                'requestRoute' => null,
                'expectedResult' => false
            ],
            'empty route' => [
                'debug' => false,
                'requestRoute' => '',
                'expectedResult' => false
            ],
            'empty route, debug' => [
                'debug' => true,
                'requestRoute' => '',
                'expectedResult' => false
            ],
            'matching route' => [
                'debug' => false,
                'requestRoute' => 'route_1',
                'expectedResult' => true
            ],
            'matching route, debug' => [
                'debug' => true,
                'requestRoute' => 'route_1',
                'expectedResult' => true
            ],
            'not matching route' => [
                'debug' => false,
                'requestRoute' => 'another',
                'expectedResult' => false
            ],
            'not matching route, debug' => [
                'debug' => true,
                'requestRoute' => 'another',
                'expectedResult' => false
            ],
            'matching debug route' => [
                'debug' => false,
                'requestRoute' => '_route_started_with_underscore',
                'expectedResult' => true
            ],
            'matching debug route, debug' => [
                'debug' => true,
                'requestRoute' => '_route_started_with_underscore',
                'expectedResult' => true
            ],
            'not matching debug route' => [
                'debug' => false,
                'requestRoute' => '_another',
                'expectedResult' => false
            ],
            'not matching debug route, debug' => [
                'debug' => true,
                'requestRoute' => '_another',
                'expectedResult' => true
            ]
        ];
    }

    public function testIsAllowedQueryWhenNoCurrentRequest(): void
    {
        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            null,
            [],
            ['some' => 'attribute'],
            [],
            [],
            false,
            null
        );

        self::assertFalse($maintenanceRestrictionsChecker->isAllowedQuery());
    }

    /**
     * @dataProvider isAllowedQueryDataProvider
     */
    public function testIsAllowedQuery(string $method, ?array $query, bool $expectedResult): void
    {
        $request = Request::create('http://test.com/test?foo=&bar=baz-value', $method);

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

    public function isAllowedQueryDataProvider(): array
    {
        return [
            'null query' => [
                'method' => 'GET',
                'query' => null,
                'expected' => false
            ],
            'empty query' => [
                'method' => 'GET',
                'query' => [],
                'expected' => false
            ],
            'matching query' => [
                'method' => 'GET',
                'query' => ['bar' => '[\w-]+val'],
                'expected' => true
            ],
            'non matching query' => [
                'method' => 'GET',
                'query' => ['bar' => '[\w-]+another'],
                'expected' => false
            ],
            'empty query attribute value' => [
                'method' => 'GET',
                'query' => ['foo' => '[\w-]+val'],
                'expected' => false
            ],
            'no query attribute' => [
                'method' => 'GET',
                'query' => ['another' => '[\w-]+val'],
                'expected' => false
            ],
            'empty pattern' => [
                'method' => 'GET',
                'query' => ['bar' => ''],
                'expected' => false
            ],
            'empty pattern, empty query attribute value' => [
                'method' => 'GET',
                'query' => ['foo' => ''],
                'expected' => false
            ],
            'matching post query' => [
                'method' => 'POST',
                'query' => ['bar' => '[\w-]+val'],
                'expected' => true
            ]
        ];
    }

    public function testIsAllowedCookieWhenNoCurrentRequest(): void
    {
        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            null,
            [],
            [],
            ['some' => 'attribute'],
            [],
            false,
            null
        );

        self::assertFalse($maintenanceRestrictionsChecker->isAllowedCookie());
    }

    /**
     * @dataProvider isAllowedCookieDataProvider
     */
    public function testIsAllowedCookie(?array $cookies, bool $expectedResult): void
    {
        $request = Request::create('http://test.com/test', 'GET', [], ['foo' => '', 'bar' => 'baz-value']);

        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            null,
            [],
            [],
            $cookies,
            [],
            false,
            $request
        );

        self::assertEquals($expectedResult, $maintenanceRestrictionsChecker->isAllowedCookie());
    }

    public function isAllowedCookieDataProvider(): array
    {
        return [
            'null cookies' => [
                'cookies' => null,
                'expected' => false
            ],
            'empty cookies' => [
                'cookies' => [],
                'expected' => false
            ],
            'matching cookies' => [
                'cookies' => ['bar' => '[\w-]+val'],
                'expected' => true
            ],
            'non matching cookies' => [
                'cookies' => ['bar' => '[\w-]+another'],
                'expected' => false
            ],
            'empty cookies attribute value' => [
                'cookies' => ['foo' => '[\w-]+val'],
                'expected' => false
            ],
            'no cookies attribute' => [
                'cookies' => ['another' => '[\w-]+val'],
                'expected' => false
            ],
            'empty pattern' => [
                'cookies' => ['bar' => ''],
                'expected' => false
            ],
            'empty pattern, empty cookies attribute value' => [
                'cookies' => ['foo' => ''],
                'expected' => false
            ]
        ];
    }

    public function testIsAllowedHostWhenNoCurrentRequest(): void
    {
        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            'test.com',
            null,
            [],
            [],
            [],
            [],
            false,
            null
        );

        self::assertFalse($maintenanceRestrictionsChecker->isAllowedHost());
    }

    /**
     * @dataProvider isAllowedHostDataProvider
     */
    public function testIsAllowedHost(?string $host, bool $expectedResult): void
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

    public function isAllowedHostDataProvider(): array
    {
        return [
            'null host' => [
                'host' => null,
                'expected' => false
            ],
            'empty host' => [
                'host' => '',
                'expected' => false
            ],
            'matching host' => [
                'host' => 'test.com',
                'expected' => true
            ],
            'matching host, case insensitive' => [
                'host' => 'Test.com',
                'expected' => true
            ],
            'non matching host' => [
                'host' => 'www.google.com',
                'expected' => false
            ]
        ];
    }

    public function testIsAllowedAttributesWhenNoCurrentRequest(): void
    {
        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            null,
            [],
            [],
            [],
            ['some' => 'attribute'],
            false,
            null
        );

        self::assertFalse($maintenanceRestrictionsChecker->isAllowedAttributes());
    }

    /**
     * @dataProvider isAllowedAttributesDataProvider
     */
    public function testIsAllowedAttributes(?array $attributes, bool $expectedResult): void
    {
        $request = Request::create('http://test.com/test');
        $request->attributes->set('foo', '');
        $request->attributes->set('bar', 'baz-value');

        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            null,
            null,
            null,
            [],
            [],
            [],
            $attributes,
            false,
            $request
        );

        self::assertEquals($expectedResult, $maintenanceRestrictionsChecker->isAllowedAttributes());
    }

    public function isAllowedAttributesDataProvider(): array
    {
        return [
            'null attributes' => [
                'attributes' => null,
                'expected' => false
            ],
            'empty attributes' => [
                'attributes' => [],
                'expected' => false
            ],
            'matching attributes' => [
                'attributes' => ['bar' => '[\w-]+val'],
                'expected' => true
            ],
            'non matching attributes' => [
                'attributes' => ['bar' => '[\w-]+another'],
                'expected' => false
            ],
            'empty attributes attribute value' => [
                'attributes' => ['foo' => '[\w-]+val'],
                'expected' => false
            ],
            'no attributes attribute' => [
                'attributes' => ['another' => '[\w-]+val'],
                'expected' => false
            ],
            'empty pattern' => [
                'attributes' => ['bar' => ''],
                'expected' => false
            ],
            'empty pattern, empty attributes attribute value' => [
                'attributes' => ['foo' => ''],
                'expected' => false
            ]
        ];
    }

    public function testIsAllowedPathWhenNoCurrentRequest(): void
    {
        $maintenanceRestrictionsChecker = $this->getMaintenanceRestrictionsChecker(
            '/bar',
            null,
            null,
            [],
            [],
            [],
            [],
            false,
            null
        );

        self::assertFalse($maintenanceRestrictionsChecker->isAllowedPath());
    }

    /**
     * @dataProvider isAllowedPathDataProvider
     */
    public function testIsAllowedPath(?string $path, bool $expectedResult): void
    {
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

    public function isAllowedPathDataProvider(): array
    {
        return [
            'null path' => [
                'path' => null,
                'expectedResult' => false
            ],
            'empty path' => [
                'path' => '',
                'expectedResult' => false
            ],
            'matching path' => [
                'path' => '/foo',
                'expectedResult' => true
            ],
            'non matching path' => [
                'path' => '/bar',
                'expectedResult' => false
            ]
        ];
    }
}
