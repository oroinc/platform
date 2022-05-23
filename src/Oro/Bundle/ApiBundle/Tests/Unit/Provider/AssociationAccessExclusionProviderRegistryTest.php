<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\AssociationAccessExclusionProviderInterface;
use Oro\Bundle\ApiBundle\Provider\AssociationAccessExclusionProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\ChainAssociationAccessExclusionProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

class AssociationAccessExclusionProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var AssociationAccessExclusionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultProvider;

    /** @var AssociationAccessExclusionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $firstProvider;

    /** @var AssociationAccessExclusionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $secondProvider;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    protected function setUp(): void
    {
        $this->defaultProvider = $this->getMockBuilder(AssociationAccessExclusionProviderInterface::class)
            ->setMockClassName('DefaultAssociationAccessExclusionProvider')
            ->getMock();
        $this->firstProvider = $this->getMockBuilder(AssociationAccessExclusionProviderInterface::class)
            ->setMockClassName('FirstAssociationAccessExclusionProvider')
            ->getMock();
        $this->secondProvider = $this->getMockBuilder(AssociationAccessExclusionProviderInterface::class)
            ->setMockClassName('SecondAssociationAccessExclusionProvider')
            ->getMock();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function getRegistry(array $providers): AssociationAccessExclusionProviderRegistry
    {
        return new AssociationAccessExclusionProviderRegistry(
            $providers,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    public function testGetAssociationAccessExclusionProviderForUnsupportedRequestType(): void
    {
        $registry = $this->getRegistry([]);

        $requestType = new RequestType(['rest', 'another']);
        $expectedProvider = new ChainAssociationAccessExclusionProvider([]);
        $provider = $registry->getAssociationAccessExclusionProvider($requestType);
        self::assertEquals($expectedProvider, $provider);
        // test memory cache
        self::assertSame($provider, $registry->getAssociationAccessExclusionProvider($requestType));
    }

    public function testGetAssociationAccessExclusionProviderForUnmatchedRequestType(): void
    {
        $registry = $this->getRegistry([
            ['default_exclusion_provider', '!first&!second'],
            ['first_exclusion_provider', 'first'],
            ['second_exclusion_provider', 'second']
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_exclusion_provider')
            ->willReturn($this->defaultProvider);

        $requestType = new RequestType(['rest']);
        $expectedProvider = new ChainAssociationAccessExclusionProvider([$this->defaultProvider]);
        $provider = $registry->getAssociationAccessExclusionProvider($requestType);
        self::assertEquals($expectedProvider, $provider);
        // test memory cache
        self::assertSame($provider, $registry->getAssociationAccessExclusionProvider($requestType));
    }

    public function testGetAssociationAccessExclusionProviderShouldReturnFirstProviderForFirstRequestType(): void
    {
        $registry = $this->getRegistry([
            ['default_exclusion_provider', '!first&!second'],
            ['first_exclusion_provider', 'first'],
            ['second_exclusion_provider', 'second']
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('first_exclusion_provider')
            ->willReturn($this->firstProvider);

        $requestType = new RequestType(['rest', 'first']);
        $expectedProvider = new ChainAssociationAccessExclusionProvider([$this->firstProvider]);
        $provider = $registry->getAssociationAccessExclusionProvider($requestType);
        self::assertEquals($expectedProvider, $provider);
        // test memory cache
        self::assertSame($provider, $registry->getAssociationAccessExclusionProvider($requestType));
    }

    public function testGetAssociationAccessExclusionProviderShouldReturnSecondProviderForSecondRequestType(): void
    {
        $registry = $this->getRegistry([
            ['default_exclusion_provider', '!first&!second'],
            ['first_exclusion_provider', 'first'],
            ['second_exclusion_provider', 'second']
        ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('second_exclusion_provider')
            ->willReturn($this->secondProvider);

        $requestType = new RequestType(['rest', 'second']);
        $expectedProvider = new ChainAssociationAccessExclusionProvider([$this->secondProvider]);
        $provider = $registry->getAssociationAccessExclusionProvider($requestType);
        self::assertEquals($expectedProvider, $provider);
        // test memory cache
        self::assertSame($provider, $registry->getAssociationAccessExclusionProvider($requestType));
    }
}
