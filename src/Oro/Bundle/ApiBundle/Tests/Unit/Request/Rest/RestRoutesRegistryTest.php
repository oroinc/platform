<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\Rest;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

class RestRoutesRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|RestRoutes */
    private $defaultProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RestRoutes */
    private $firstProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RestRoutes */
    private $secondProvider;

    protected function setUp()
    {
        $this->defaultProvider = $this->createMock(RestRoutes::class);
        $this->firstProvider = $this->createMock(RestRoutes::class);
        $this->secondProvider = $this->createMock(RestRoutes::class);
    }

    /**
     * @param array $providers
     *
     * @return RestRoutesRegistry
     */
    private function getRegistry(array $providers)
    {
        return new RestRoutesRegistry(
            $providers,
            new RequestExpressionMatcher()
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot find a routes provider for the request "rest,another".
     */
    public function testGetRoutesForUnsupportedRequestType()
    {
        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getRoutes($requestType);
    }

    public function testGetRoutesShouldReturnDefaultProviderForNotFirstAndSecondRequestType()
    {
        $registry = $this->getRegistry(
            [
                [$this->firstProvider, 'first&rest'],
                [$this->secondProvider, 'second&rest'],
                [$this->defaultProvider, 'rest']
            ]
        );

        $requestType = new RequestType(['rest']);
        self::assertSame($this->defaultProvider, $registry->getRoutes($requestType));
    }

    public function testGetRoutesShouldReturnFirstProviderForFirstRequestType()
    {
        $registry = $this->getRegistry(
            [
                [$this->firstProvider, 'first&rest'],
                [$this->secondProvider, 'second&rest'],
                [$this->defaultProvider, 'rest']
            ]
        );

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstProvider, $registry->getRoutes($requestType));
    }

    public function testGetRoutesShouldReturnSecondProviderForSecondRequestType()
    {
        $registry = $this->getRegistry(
            [
                [$this->firstProvider, 'first&rest'],
                [$this->secondProvider, 'second&rest'],
                [$this->defaultProvider, 'rest']
            ]
        );

        $requestType = new RequestType(['rest', 'second', 'another']);
        self::assertSame($this->secondProvider, $registry->getRoutes($requestType));
    }

    public function testGetRoutesShouldReturnDefaultBagIfSpecificBagNotFound()
    {
        $registry = $this->getRegistry(
            [
                [$this->firstProvider, 'first'],
                [$this->defaultProvider, '']
            ]
        );

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($this->defaultProvider, $registry->getRoutes($requestType));
    }
}
