<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\EntityAliasResolverRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityAliasResolverRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityAliasResolver */
    private $defaultResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityAliasResolver */
    private $firstResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityAliasResolver */
    private $secondResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    protected function setUp()
    {
        $this->defaultResolver = $this->createMock(EntityAliasResolver::class);
        $this->firstResolver = $this->createMock(EntityAliasResolver::class);
        $this->secondResolver = $this->createMock(EntityAliasResolver::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @param array $entityAliasResolvers
     *
     * @return EntityAliasResolverRegistry
     */
    private function getRegistry(array $entityAliasResolvers)
    {
        return new EntityAliasResolverRegistry(
            $entityAliasResolvers,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot find an entity alias resolver for the request "rest,another".
     */
    public function testGetEntityAliasResolverForUnsupportedRequestType()
    {
        $requestType = new RequestType(['rest', 'another']);
        $registry = $this->getRegistry([]);
        $registry->getEntityAliasResolver($requestType);
    }

    public function testGetEntityAliasResolverShouldReturnDefaultResolverForNotFirstAndSecondRequestType()
    {
        $registry = $this->getRegistry(
            [
                ['default_entity_alias_resolver', '!first&!second'],
                ['first_entity_alias_resolver', 'first'],
                ['second_entity_alias_resolver', 'second']
            ]
        );

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_entity_alias_resolver')
            ->willReturn($this->defaultResolver);

        $requestType = new RequestType(['rest']);
        self::assertSame($this->defaultResolver, $registry->getEntityAliasResolver($requestType));
        // test internal cache
        self::assertSame($this->defaultResolver, $registry->getEntityAliasResolver($requestType));
    }

    public function testGetEntityAliasResolverShouldReturnFirstResolverForFirstRequestType()
    {
        $registry = $this->getRegistry(
            [
                ['default_entity_alias_resolver', '!first&!second'],
                ['first_entity_alias_resolver', 'first'],
                ['second_entity_alias_resolver', 'second']
            ]
        );

        $this->container->expects(self::once())
            ->method('get')
            ->with('first_entity_alias_resolver')
            ->willReturn($this->firstResolver);

        $requestType = new RequestType(['rest', 'first']);
        self::assertSame($this->firstResolver, $registry->getEntityAliasResolver($requestType));
        // test internal cache
        self::assertSame($this->firstResolver, $registry->getEntityAliasResolver($requestType));
    }

    public function testGetEntityAliasResolverShouldReturnSecondResolverForSecondRequestType()
    {
        $registry = $this->getRegistry(
            [
                ['default_entity_alias_resolver', '!first&!second'],
                ['first_entity_alias_resolver', 'first'],
                ['second_entity_alias_resolver', 'second']
            ]
        );

        $this->container->expects(self::once())
            ->method('get')
            ->with('second_entity_alias_resolver')
            ->willReturn($this->secondResolver);

        $requestType = new RequestType(['rest', 'second']);
        self::assertSame($this->secondResolver, $registry->getEntityAliasResolver($requestType));
        // test internal cache
        self::assertSame($this->secondResolver, $registry->getEntityAliasResolver($requestType));
    }

    public function testGetEntityAliasResolverShouldReturnDefaultBagIfSpecificBagNotFound()
    {
        $registry = $this->getRegistry(
            [
                ['first_entity_alias_resolver', 'first'],
                ['default_entity_alias_resolver', '']
            ]
        );

        $this->container->expects(self::once())
            ->method('get')
            ->with('default_entity_alias_resolver')
            ->willReturn($this->defaultResolver);

        $requestType = new RequestType(['rest', 'another']);
        self::assertSame($this->defaultResolver, $registry->getEntityAliasResolver($requestType));
        // test internal cache
        self::assertSame($this->defaultResolver, $registry->getEntityAliasResolver($requestType));
    }

    public function testWarmUpCache()
    {
        $registry = $this->getRegistry(
            [
                ['default_entity_alias_resolver', '!first&!second'],
                ['first_entity_alias_resolver', 'first'],
                ['second_entity_alias_resolver', 'second']
            ]
        );

        $this->container->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                [
                    'default_entity_alias_resolver',
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->defaultResolver
                ],
                [
                    'first_entity_alias_resolver',
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->firstResolver
                ],
                [
                    'second_entity_alias_resolver',
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->secondResolver
                ]
            ]);

        $this->defaultResolver->expects(self::once())
            ->method('warmUpCache');
        $this->firstResolver->expects(self::once())
            ->method('warmUpCache');
        $this->secondResolver->expects(self::once())
            ->method('warmUpCache');

        $registry->warmUpCache();
    }

    public function testClearCache()
    {
        $registry = $this->getRegistry(
            [
                ['default_entity_alias_resolver', '!first&!second'],
                ['first_entity_alias_resolver', 'first'],
                ['second_entity_alias_resolver', 'second']
            ]
        );

        $this->container->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                [
                    'default_entity_alias_resolver',
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->defaultResolver
                ],
                [
                    'first_entity_alias_resolver',
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->firstResolver
                ],
                [
                    'second_entity_alias_resolver',
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->secondResolver
                ]
            ]);

        $this->defaultResolver->expects(self::once())
            ->method('clearCache');
        $this->firstResolver->expects(self::once())
            ->method('clearCache');
        $this->secondResolver->expects(self::once())
            ->method('clearCache');

        $registry->clearCache();
    }
}
