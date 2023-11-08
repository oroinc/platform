<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OwnershipMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var OwnershipMetadataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->cache = $this->createMock(AbstractAdapter::class);

        $this->provider = new OwnershipMetadataProvider(
            [
                'organization' => 'AcmeBundle:Organization',
                'business_unit' => 'AcmeBundle:BusinessUnit',
                'user' => 'AcmeBundle:User',
            ],
            $this->configManager,
            $this->entityClassResolver,
            $this->tokenAccessor,
            $this->cache
        );
    }

    public function testGetUserClass(): void
    {
        $this->entityClassResolver->expects(self::exactly(3))
            ->method('getEntityClass')
            ->willReturnMap([
                ['AcmeBundle:User', 'AcmeBundle\Entity\User'],
                ['AcmeBundle:BusinessUnit', 'AcmeBundle\Entity\BusinessUnit'],
                ['AcmeBundle:Organization', 'AcmeBundle\Entity\Organization'],
            ]);

        self::assertEquals('AcmeBundle\Entity\User', $this->provider->getUserClass());
        // test that the class is cached in a local property
        self::assertEquals('AcmeBundle\Entity\User', $this->provider->getUserClass());
    }

    public function testGetBusinessUnitClass(): void
    {
        $this->entityClassResolver->expects(self::exactly(3))
            ->method('getEntityClass')
            ->willReturnMap([
                ['AcmeBundle:User', 'AcmeBundle\Entity\User'],
                ['AcmeBundle:BusinessUnit', 'AcmeBundle\Entity\BusinessUnit'],
                ['AcmeBundle:Organization', 'AcmeBundle\Entity\Organization'],
            ]);

        self::assertEquals('AcmeBundle\Entity\BusinessUnit', $this->provider->getBusinessUnitClass());
        // test that the class is cached in a local property
        self::assertEquals('AcmeBundle\Entity\BusinessUnit', $this->provider->getBusinessUnitClass());
    }

    public function testGetOrganizationClass(): void
    {
        $this->entityClassResolver->expects(self::exactly(3))
            ->method('getEntityClass')
            ->willReturnMap([
                ['AcmeBundle:User', 'AcmeBundle\Entity\User'],
                ['AcmeBundle:BusinessUnit', 'AcmeBundle\Entity\BusinessUnit'],
                ['AcmeBundle:Organization', 'AcmeBundle\Entity\Organization'],
            ]);

        self::assertEquals('AcmeBundle\Entity\Organization', $this->provider->getOrganizationClass());
        // test that the class is cached in a local property
        self::assertEquals('AcmeBundle\Entity\Organization', $this->provider->getOrganizationClass());
    }

    public function testGetMetadataUndefinedClassWithoutCache(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with('UndefinedClass')
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');

        $this->entityClassResolver = null;
        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        self::assertEquals(new OwnershipMetadata(), $this->provider->getMetadata('UndefinedClass'));
    }

    public function testGetMetadataWithoutCache(): void
    {
        $config = new Config(new EntityConfigId('ownership', \stdClass::class));
        $config->set('owner_type', 'USER');
        $config->set('owner_field_name', 'test_field');
        $config->set('owner_column_name', 'test_column');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(\stdClass::class)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('ownership', \stdClass::class)
            ->willReturn($config);

        $this->entityClassResolver = null;
        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        self::assertEquals(
            new OwnershipMetadata('USER', 'test_field', 'test_column'),
            $this->provider->getMetadata(\stdClass::class)
        );
    }

    public function testGetMetadataSetsOrganizationFieldName(): void
    {
        $config = new Config(new EntityConfigId('ownership', \stdClass::class));
        $config->set('owner_type', 'ORGANIZATION');
        $config->set('owner_field_name', 'test_field');
        $config->set('owner_column_name', 'test_column');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(\stdClass::class)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('ownership', \stdClass::class)
            ->willReturn($config);

        $this->entityClassResolver = null;
        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        self::assertEquals(
            new OwnershipMetadata('ORGANIZATION', 'test_field', 'test_column', 'test_field', 'test_column'),
            $this->provider->getMetadata(\stdClass::class)
        );
    }

    public function testGetMetadataUndefinedClassWithCache(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with('UndefinedClass')
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');

        $this->cache->expects(self::exactly(2))
            ->method('get')
            ->with('UndefinedClass')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function ($cacheKey, $callback) {
                    return $callback($this->createMock(ItemInterface::class));
                }),
                true
            );

        $this->entityClassResolver = null;

        $providerWithCleanLocalCache = clone $this->provider;
        $metadata = new OwnershipMetadata();

        // no cache
        self::assertEquals($metadata, $this->provider->getMetadata('UndefinedClass'));
        // local cache
        self::assertEquals($metadata, $this->provider->getMetadata('UndefinedClass'));
        // cache
        self::assertEquals($metadata, $providerWithCleanLocalCache->getMetadata('UndefinedClass'));
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(?object $user, bool $expectedResult): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        self::assertEquals($expectedResult, $this->provider->supports());
    }

    public function supportsDataProvider(): array
    {
        return [
            'without user' => [
                'user' => null,
                'expectedResult' => false,
            ],
            'unsupported user' => [
                'user' => new \stdClass(),
                'expectedResult' => false,
            ],
            'supported user' => [
                'user' => new User(),
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider owningEntityNamesDataProvider
     */
    public function testInvalidOwningEntityNames(array $owningEntityNames): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The $owningEntityNames must contains "organization", "business_unit" and "user" keys.'
        );

        $provider = new OwnershipMetadataProvider(
            $owningEntityNames,
            $this->configManager,
            $this->entityClassResolver,
            $this->tokenAccessor,
            $this->cache
        );
        $provider->getUserClass();
    }

    public function owningEntityNamesDataProvider(): array
    {
        return [
            [
                'owningEntityNames' => [],
            ],
            [
                'owningEntityNames' => [
                    'organization' => 'AcmeBundle\Entity\Organization',
                ],
            ],
            [
                'owningEntityNames' => [
                    'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                ],
            ],
            [
                'owningEntityNames' => [
                    'user' => 'AcmeBundle\Entity\User',
                ],
            ],
            [
                'owningEntityNames' => [
                    'organization' => 'AcmeBundle\Entity\Organization',
                    'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                ],
            ],
            [
                'owningEntityNames' => [
                    'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                    'user' => 'AcmeBundle\Entity\User',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getMaxAccessLevelDataProvider
     */
    public function testGetMaxAccessLevel(int $accessLevel, ?string $object, int $expectedResult): void
    {
        if ($object && $accessLevel === AccessLevel::SYSTEM_LEVEL) {
            $config = new Config(new EntityConfigId('ownership', \stdClass::class));
            $config
                ->set('owner_type', 'USER')
                ->set('owner_field_name', 'test_field')
                ->set('owner_column_name', 'test_column');

            $this->configManager->expects(self::once())
                ->method('hasConfig')
                ->with(\stdClass::class)
                ->willReturn(true);
            $this->configManager->expects(self::once())
                ->method('getEntityConfig')
                ->with('ownership', \stdClass::class)
                ->willReturn($config);
            $this->cache->expects(self::once())
                ->method('get')
                ->willReturnCallback(function ($cacheKey, $callback) {
                    return $callback($this->createMock(ItemInterface::class));
                });
        }

        $this->entityClassResolver = null;

        self::assertEquals($expectedResult, $this->provider->getMaxAccessLevel($accessLevel, $object));
    }

    public function getMaxAccessLevelDataProvider(): array
    {
        return [
            [
                'accessLevel' => AccessLevel::GLOBAL_LEVEL,
                'object' => \stdClass::class,
                'expectedResult' => AccessLevel::GLOBAL_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::GLOBAL_LEVEL,
                'object' => null,
                'expectedResult' => AccessLevel::GLOBAL_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::DEEP_LEVEL,
                'object' => \stdClass::class,
                'expectedResult' => AccessLevel::DEEP_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::DEEP_LEVEL,
                'object' => null,
                'expectedResult' => AccessLevel::DEEP_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::LOCAL_LEVEL,
                'object' => \stdClass::class,
                'expectedResult' => AccessLevel::LOCAL_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::LOCAL_LEVEL,
                'object' => null,
                'expectedResult' => AccessLevel::LOCAL_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::BASIC_LEVEL,
                'object' => \stdClass::class,
                'expectedResult' => AccessLevel::BASIC_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::BASIC_LEVEL,
                'object' => null,
                'expectedResult' => AccessLevel::BASIC_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::SYSTEM_LEVEL,
                'object' => \stdClass::class,
                'expectedResult' => AccessLevel::GLOBAL_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::SYSTEM_LEVEL,
                'object' => null,
                'expectedResult' => AccessLevel::SYSTEM_LEVEL
            ],
        ];
    }
}
