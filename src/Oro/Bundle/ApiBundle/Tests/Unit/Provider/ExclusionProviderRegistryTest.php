<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ExclusionProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ExclusionProviderRegistryTest extends TestCase
{
    private ExclusionProviderInterface&MockObject $defaultExclusionProvider;
    private ExclusionProviderInterface&MockObject $firstExclusionProvider;
    private ExclusionProviderInterface&MockObject $secondExclusionProvider;
    private ContainerInterface&MockObject $container;

    #[\Override]
    protected function setUp(): void
    {
        $this->defaultExclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->firstExclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->secondExclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function getRegistry(array $exclusionProviders): ExclusionProviderRegistry
    {
        return new ExclusionProviderRegistry(
            $exclusionProviders,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    public function testGetExclusionProviderForUnsupportedRequestType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find an exclusion provider for the request "rest,another".');

        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getExclusionProvider($requestType);
    }

    public function testGetExclusionProviderShouldReturnDefaultProviderForNotFirstAndSecondRequestType(): void
    {
        $registry = $this->getRegistry(
            [
                ['default_exclusion_provider', '!first&!second'],
                ['first_exclusion_provider', 'first'],
                ['second_exclusion_provider', 'second']
            ]
        );

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_exclusion_provider')
            ->willReturn($this->defaultExclusionProvider);

        $requestType = new RequestType(['rest']);
        self::assertSame($this->defaultExclusionProvider, $registry->getExclusionProvider($requestType));
        // test internal cache
        self::assertSame($this->defaultExclusionProvider, $registry->getExclusionProvider($requestType));
    }

    public function testGetExclusionProviderShouldReturnFirstProviderForFirstRequestType(): void
    {
        $registry = $this->getRegistry(
            [
                ['default_exclusion_provider', '!first&!second'],
                ['first_exclusion_provider', 'first'],
                ['second_exclusion_provider', 'second']
            ]
        );

        $this->container->expects(self::once())
            ->method('get')
            ->with('first_exclusion_provider')
            ->willReturn($this->firstExclusionProvider);

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstExclusionProvider, $registry->getExclusionProvider($requestType));
        // test internal cache
        self::assertSame($this->firstExclusionProvider, $registry->getExclusionProvider($requestType));
    }

    public function testGetExclusionProviderShouldReturnSecondProviderForSecondRequestType(): void
    {
        $registry = $this->getRegistry(
            [
                ['default_exclusion_provider', '!first&!second'],
                ['first_exclusion_provider', 'first'],
                ['second_exclusion_provider', 'second']
            ]
        );

        $this->container->expects(self::once())
            ->method('get')
            ->with('second_exclusion_provider')
            ->willReturn($this->secondExclusionProvider);

        $requestType = new RequestType(['rest', 'second']);
        self::assertSame($this->secondExclusionProvider, $registry->getExclusionProvider($requestType));
        // test internal cache
        self::assertSame($this->secondExclusionProvider, $registry->getExclusionProvider($requestType));
    }

    public function testGetExclusionProviderShouldReturnDefaultBagIfSpecificBagNotFound(): void
    {
        $registry = $this->getRegistry(
            [
                ['first_exclusion_provider', 'first'],
                ['default_exclusion_provider', '']
            ]
        );

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_exclusion_provider')
            ->willReturn($this->defaultExclusionProvider);

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($this->defaultExclusionProvider, $registry->getExclusionProvider($requestType));
        // test internal cache
        self::assertSame($this->defaultExclusionProvider, $registry->getExclusionProvider($requestType));
    }
}
