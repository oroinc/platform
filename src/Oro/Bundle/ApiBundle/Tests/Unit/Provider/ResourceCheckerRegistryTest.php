<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ResourceCheckerConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourceCheckerInterface;
use Oro\Bundle\ApiBundle\Provider\ResourceCheckerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ResourceCheckerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function getRegistry(array $config): ResourceCheckerRegistry
    {
        return new ResourceCheckerRegistry($config, $this->container, new RequestExpressionMatcher());
    }

    public function testGetResourceTypeForUnsupportedRequestType()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find a resource type for the request "rest,another".');

        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getResourceType($requestType);
    }

    public function testGetResourceTypeShouldReturnDefaultProviderForNotFirstAndSecondRequestType(): void
    {
        $registry = $this->getRegistry([
            ['default_type', 'default_config_provider', 'default_checker', '!first&!second'],
            ['first_type', 'first_config_provider', 'first_checker', 'first'],
            ['second_type', 'second_config_provider', 'second_checker', 'second']
        ]);

        $requestType = new RequestType(['rest']);
        self::assertSame('default_type', $registry->getResourceType($requestType));
        // test internal cache
        self::assertSame('default_type', $registry->getResourceType($requestType));
    }

    public function testGetResourceTypeShouldReturnFirstProviderForFirstRequestType(): void
    {
        $registry = $this->getRegistry([
            ['first_type', 'first_config_provider', 'first_checker', 'first'],
            ['second_type', 'second_config_provider', 'second_checker', 'second'],
            ['default_type', 'default_config_provider', 'default_checker', null]
        ]);

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame('first_type', $registry->getResourceType($requestType));
        // test internal cache
        self::assertSame('first_type', $registry->getResourceType($requestType));
    }

    public function testGetResourceTypeShouldReturnSecondProviderForSecondRequestType(): void
    {
        $registry = $this->getRegistry([
            ['first_type', 'first_config_provider', 'first_checker', 'first'],
            ['second_type', 'second_config_provider', 'second_checker', 'second'],
            ['default_type', 'default_config_provider', 'default_checker', null]
        ]);

        $requestType = new RequestType(['rest', 'second']);
        self::assertSame('second_type', $registry->getResourceType($requestType));
        // test internal cache
        self::assertSame('second_type', $registry->getResourceType($requestType));
    }

    public function testGetResourceTypeShouldReturnDefaultBagIfSpecificBagNotFound(): void
    {
        $registry = $this->getRegistry([
            ['first_type', 'first_config_provider', 'first_checker', 'first'],
            ['default_type', 'default_config_provider', 'default_checker', null]
        ]);

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame('default_type', $registry->getResourceType($requestType));
        // test internal cache
        self::assertSame('default_type', $registry->getResourceType($requestType));
    }

    public function testGetConfigProviderForUnsupportedRequestType()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find a resource checker config provider for the request "rest,another".');

        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getResourceCheckerConfigProvider($requestType);
    }

    public function testGetConfigProviderShouldReturnDefaultProviderForNotFirstAndSecondRequestType(): void
    {
        $registry = $this->getRegistry([
            ['default_type', 'default_config_provider', 'default_checker', '!first&!second'],
            ['first_type', 'first_config_provider', 'first_checker', 'first'],
            ['second_type', 'second_config_provider', 'second_checker', 'second']
        ]);

        $configProvider = $this->createMock(ResourceCheckerConfigProvider::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('default_config_provider')
            ->willReturn($configProvider);

        $requestType = new RequestType(['rest']);
        self::assertSame($configProvider, $registry->getResourceCheckerConfigProvider($requestType));
        // test internal cache
        self::assertSame($configProvider, $registry->getResourceCheckerConfigProvider($requestType));
    }

    public function testGetConfigProviderShouldReturnFirstProviderForFirstRequestType(): void
    {
        $registry = $this->getRegistry([
            ['first_type', 'first_config_provider', 'first_checker', 'first'],
            ['second_type', 'second_config_provider', 'second_checker', 'second'],
            ['default_type', 'default_config_provider', 'default_checker', null]
        ]);

        $configProvider = $this->createMock(ResourceCheckerConfigProvider::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('first_config_provider')
            ->willReturn($configProvider);

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($configProvider, $registry->getResourceCheckerConfigProvider($requestType));
        // test internal cache
        self::assertSame($configProvider, $registry->getResourceCheckerConfigProvider($requestType));
    }

    public function testGetConfigProviderShouldReturnSecondProviderForSecondRequestType(): void
    {
        $registry = $this->getRegistry([
            ['first_type', 'first_config_provider', 'first_checker', 'first'],
            ['second_type', 'second_config_provider', 'second_checker', 'second'],
            ['default_type', 'default_config_provider', 'default_checker', null]
        ]);

        $configProvider = $this->createMock(ResourceCheckerConfigProvider::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('second_config_provider')
            ->willReturn($configProvider);

        $requestType = new RequestType(['rest', 'second']);
        self::assertSame($configProvider, $registry->getResourceCheckerConfigProvider($requestType));
        // test internal cache
        self::assertSame($configProvider, $registry->getResourceCheckerConfigProvider($requestType));
    }

    public function testGetConfigProviderShouldReturnDefaultBagIfSpecificBagNotFound(): void
    {
        $registry = $this->getRegistry([
            ['first_type', 'first_config_provider', 'first_checker', 'first'],
            ['default_type', 'default_config_provider', 'default_checker', null]
        ]);

        $configProvider = $this->createMock(ResourceCheckerConfigProvider::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('default_config_provider')
            ->willReturn($configProvider);

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($configProvider, $registry->getResourceCheckerConfigProvider($requestType));
        // test internal cache
        self::assertSame($configProvider, $registry->getResourceCheckerConfigProvider($requestType));
    }

    public function testGetResourceCheckerForUnsupportedRequestType()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find a resource checker for the request "rest,another".');

        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getResourceChecker($requestType);
    }

    public function testGetResourceCheckerShouldReturnDefaultProviderForNotFirstAndSecondRequestType(): void
    {
        $registry = $this->getRegistry([
            ['default_type', 'default_config_provider', 'default_checker', '!first&!second'],
            ['first_type', 'first_config_provider', 'first_checker', 'first'],
            ['second_type', 'second_config_provider', 'second_checker', 'second']
        ]);

        $resourceChecker = $this->createMock(ResourceCheckerInterface::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('default_checker')
            ->willReturn($resourceChecker);

        $requestType = new RequestType(['rest']);
        self::assertSame($resourceChecker, $registry->getResourceChecker($requestType));
        // test internal cache
        self::assertSame($resourceChecker, $registry->getResourceChecker($requestType));
    }

    public function testGetResourceCheckerShouldReturnFirstProviderForFirstRequestType(): void
    {
        $registry = $this->getRegistry([
            ['first_type', 'first_config_provider', 'first_checker', 'first'],
            ['second_type', 'second_config_provider', 'second_checker', 'second'],
            ['default_type', 'default_config_provider', 'default_checker', null]
        ]);

        $resourceChecker = $this->createMock(ResourceCheckerInterface::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('first_checker')
            ->willReturn($resourceChecker);

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($resourceChecker, $registry->getResourceChecker($requestType));
        // test internal cache
        self::assertSame($resourceChecker, $registry->getResourceChecker($requestType));
    }

    public function testGetResourceCheckerShouldReturnSecondProviderForSecondRequestType(): void
    {
        $registry = $this->getRegistry([
            ['first_type', 'first_config_provider', 'first_checker', 'first'],
            ['second_type', 'second_config_provider', 'second_checker', 'second'],
            ['default_type', 'default_config_provider', 'default_checker', null]
        ]);

        $resourceChecker = $this->createMock(ResourceCheckerInterface::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('second_checker')
            ->willReturn($resourceChecker);

        $requestType = new RequestType(['rest', 'second']);
        self::assertSame($resourceChecker, $registry->getResourceChecker($requestType));
        // test internal cache
        self::assertSame($resourceChecker, $registry->getResourceChecker($requestType));
    }

    public function testGetResourceCheckerShouldReturnDefaultBagIfSpecificBagNotFound(): void
    {
        $registry = $this->getRegistry([
            ['first_type', 'first_config_provider', 'first_checker', 'first'],
            ['default_type', 'default_config_provider', 'default_checker', null]
        ]);

        $resourceChecker = $this->createMock(ResourceCheckerInterface::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('default_checker')
            ->willReturn($resourceChecker);

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($resourceChecker, $registry->getResourceChecker($requestType));
        // test internal cache
        self::assertSame($resourceChecker, $registry->getResourceChecker($requestType));
    }
}
