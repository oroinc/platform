<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class EntityOverrideProviderRegistryTest extends TestCase
{
    private EntityOverrideProviderInterface&MockObject $defaultProvider;
    private EntityOverrideProviderInterface&MockObject $firstProvider;
    private EntityOverrideProviderInterface&MockObject $secondProvider;
    private ContainerInterface&MockObject $container;

    #[\Override]
    protected function setUp(): void
    {
        $this->defaultProvider = $this->createMock(EntityOverrideProviderInterface::class);
        $this->firstProvider = $this->createMock(EntityOverrideProviderInterface::class);
        $this->secondProvider = $this->createMock(EntityOverrideProviderInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function getRegistry(array $entityOverrideProviders): EntityOverrideProviderRegistry
    {
        return new EntityOverrideProviderRegistry(
            $entityOverrideProviders,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    public function testGetEntityOverrideProviderForUnsupportedRequestType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find an entity override provider for the request "rest,another".');

        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getEntityOverrideProvider($requestType);
    }

    public function testGetEntityOverrideProviderShouldReturnDefaultProviderForNotFirstAndSecondRequestType(): void
    {
        $registry = $this->getRegistry(
            [
                ['default_entity_override_provider', '!first&!second'],
                ['first_entity_override_provider', 'first'],
                ['second_entity_override_provider', 'second']
            ]
        );

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_entity_override_provider')
            ->willReturn($this->defaultProvider);

        $requestType = new RequestType(['rest']);
        self::assertSame($this->defaultProvider, $registry->getEntityOverrideProvider($requestType));
        // test internal cache
        self::assertSame($this->defaultProvider, $registry->getEntityOverrideProvider($requestType));
    }

    public function testGetEntityOverrideProviderShouldReturnFirstProviderForFirstRequestType(): void
    {
        $registry = $this->getRegistry(
            [
                ['default_entity_override_provider', '!first&!second'],
                ['first_entity_override_provider', 'first'],
                ['second_entity_override_provider', 'second']
            ]
        );

        $this->container->expects(self::once())
            ->method('get')
            ->with('first_entity_override_provider')
            ->willReturn($this->firstProvider);

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstProvider, $registry->getEntityOverrideProvider($requestType));
        // test internal cache
        self::assertSame($this->firstProvider, $registry->getEntityOverrideProvider($requestType));
    }

    public function testGetEntityOverrideProviderShouldReturnSecondProviderForSecondRequestType(): void
    {
        $registry = $this->getRegistry(
            [
                ['default_entity_override_provider', '!first&!second'],
                ['first_entity_override_provider', 'first'],
                ['second_entity_override_provider', 'second']
            ]
        );

        $this->container->expects(self::once())
            ->method('get')
            ->with('second_entity_override_provider')
            ->willReturn($this->secondProvider);

        $requestType = new RequestType(['rest', 'second']);
        self::assertSame($this->secondProvider, $registry->getEntityOverrideProvider($requestType));
        // test internal cache
        self::assertSame($this->secondProvider, $registry->getEntityOverrideProvider($requestType));
    }

    public function testGetEntityOverrideProviderShouldReturnDefaultBagIfSpecificBagNotFound(): void
    {
        $registry = $this->getRegistry(
            [
                ['first_entity_override_provider', 'first'],
                ['default_entity_override_provider', '']
            ]
        );

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_entity_override_provider')
            ->willReturn($this->defaultProvider);

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($this->defaultProvider, $registry->getEntityOverrideProvider($requestType));
        // test internal cache
        self::assertSame($this->defaultProvider, $registry->getEntityOverrideProvider($requestType));
    }
}
