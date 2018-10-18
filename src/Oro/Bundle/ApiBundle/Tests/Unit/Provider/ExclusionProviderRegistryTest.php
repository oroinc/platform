<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ExclusionProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExclusionProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ExclusionProviderInterface */
    private $defaultExclusionProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExclusionProviderInterface */
    private $firstExclusionProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExclusionProviderInterface */
    private $secondExclusionProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    protected function setUp()
    {
        $this->defaultExclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->firstExclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->secondExclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @param array $exclusionProviders
     *
     * @return ExclusionProviderRegistry
     */
    private function getRegistry(array $exclusionProviders)
    {
        return new ExclusionProviderRegistry(
            $exclusionProviders,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot find an exclusion provider for the request "rest,another".
     */
    public function testGetExclusionProviderForUnsupportedRequestType()
    {
        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getExclusionProvider($requestType);
    }

    public function testGetExclusionProviderShouldReturnDefaultProviderForNotFirstAndSecondRequestType()
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

    public function testGetExclusionProviderShouldReturnFirstProviderForFirstRequestType()
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

    public function testGetExclusionProviderShouldReturnSecondProviderForSecondRequestType()
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

    public function testGetExclusionProviderShouldReturnDefaultBagIfSpecificBagNotFound()
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
