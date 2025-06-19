<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\Rest;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RestRoutesRegistryTest extends TestCase
{
    private RestRoutes&MockObject $defaultProvider;
    private RestRoutes&MockObject $firstProvider;
    private RestRoutes&MockObject $secondProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->defaultProvider = $this->createMock(RestRoutes::class);
        $this->firstProvider = $this->createMock(RestRoutes::class);
        $this->secondProvider = $this->createMock(RestRoutes::class);
    }

    private function getRegistry(array $providers): RestRoutesRegistry
    {
        return new RestRoutesRegistry(
            $providers,
            TestContainerBuilder::create()
                ->add('default_provider', $this->defaultProvider)
                ->add('first_provider', $this->firstProvider)
                ->add('second_provider', $this->secondProvider)
                ->getContainer($this),
            new RequestExpressionMatcher()
        );
    }

    public function testGetRoutesForUnsupportedRequestType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find a routes provider for the request "rest,another".');

        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getRoutes($requestType);
    }

    public function testGetRoutesShouldReturnDefaultProviderForNotFirstAndSecondRequestType(): void
    {
        $registry = $this->getRegistry(
            [
                ['first_provider', 'first&rest'],
                ['second_provider', 'second&rest'],
                ['default_provider', 'rest']
            ]
        );

        $requestType = new RequestType(['rest']);
        self::assertSame($this->defaultProvider, $registry->getRoutes($requestType));
    }

    public function testGetRoutesShouldReturnFirstProviderForFirstRequestType(): void
    {
        $registry = $this->getRegistry(
            [
                ['first_provider', 'first&rest'],
                ['second_provider', 'second&rest'],
                ['default_provider', 'rest']
            ]
        );

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstProvider, $registry->getRoutes($requestType));
    }

    public function testGetRoutesShouldReturnSecondProviderForSecondRequestType(): void
    {
        $registry = $this->getRegistry(
            [
                ['first_provider', 'first&rest'],
                ['second_provider', 'second&rest'],
                ['default_provider', 'rest']
            ]
        );

        $requestType = new RequestType(['rest', 'second', 'another']);
        self::assertSame($this->secondProvider, $registry->getRoutes($requestType));
    }

    public function testGetRoutesShouldReturnDefaultBagIfSpecificBagNotFound(): void
    {
        $registry = $this->getRegistry(
            [
                ['first_provider', 'first'],
                ['default_provider', '']
            ]
        );

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($this->defaultProvider, $registry->getRoutes($requestType));
    }
}
