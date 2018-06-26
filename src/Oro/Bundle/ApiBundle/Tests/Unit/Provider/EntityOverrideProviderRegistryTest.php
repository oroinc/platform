<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityOverrideProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityOverrideProviderInterface */
    private $defaultProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityOverrideProviderInterface */
    private $firstProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityOverrideProviderInterface */
    private $secondProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    protected function setUp()
    {
        $this->defaultProvider = $this->createMock(EntityOverrideProviderInterface::class);
        $this->firstProvider = $this->createMock(EntityOverrideProviderInterface::class);
        $this->secondProvider = $this->createMock(EntityOverrideProviderInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @param array $entityOverrideProviders
     *
     * @return EntityOverrideProviderRegistry
     */
    private function getRegistry(array $entityOverrideProviders)
    {
        return new EntityOverrideProviderRegistry(
            $entityOverrideProviders,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot find an entity override provider for the request "rest,another".
     */
    public function testGetEntityOverrideProviderForUnsupportedRequestType()
    {
        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getEntityOverrideProvider($requestType);
    }

    public function testGetEntityOverrideProviderShouldReturnDefaultProviderForNotFirstAndSecondRequestType()
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

    public function testGetEntityOverrideProviderShouldReturnFirstProviderForFirstRequestType()
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

    public function testGetEntityOverrideProviderShouldReturnSecondProviderForSecondRequestType()
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

    public function testGetEntityOverrideProviderShouldReturnDefaultBagIfSpecificBagNotFound()
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
