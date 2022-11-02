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
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityClassResolver */
    private $entityClassResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface */
    private $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AbstractAdapter */
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

    public function testGetUserClass()
    {
        $this->entityClassResolver->expects($this->exactly(3))
            ->method('getEntityClass')
            ->willReturnMap([
                ['AcmeBundle:User', 'AcmeBundle\Entity\User'],
                ['AcmeBundle:BusinessUnit', 'AcmeBundle\Entity\BusinessUnit'],
                ['AcmeBundle:Organization', 'AcmeBundle\Entity\Organization'],
            ]);

        $this->assertEquals('AcmeBundle\Entity\User', $this->provider->getUserClass());
        // test that the class is cached in a local property
        $this->assertEquals('AcmeBundle\Entity\User', $this->provider->getUserClass());
    }

    public function testGetBusinessUnitClass()
    {
        $this->entityClassResolver->expects($this->exactly(3))
            ->method('getEntityClass')
            ->willReturnMap([
                ['AcmeBundle:User', 'AcmeBundle\Entity\User'],
                ['AcmeBundle:BusinessUnit', 'AcmeBundle\Entity\BusinessUnit'],
                ['AcmeBundle:Organization', 'AcmeBundle\Entity\Organization'],
            ]);

        $this->assertEquals('AcmeBundle\Entity\BusinessUnit', $this->provider->getBusinessUnitClass());
        // test that the class is cached in a local property
        $this->assertEquals('AcmeBundle\Entity\BusinessUnit', $this->provider->getBusinessUnitClass());
    }

    public function testGetOrganizationClass()
    {
        $this->entityClassResolver->expects($this->exactly(3))
            ->method('getEntityClass')
            ->willReturnMap([
                ['AcmeBundle:User', 'AcmeBundle\Entity\User'],
                ['AcmeBundle:BusinessUnit', 'AcmeBundle\Entity\BusinessUnit'],
                ['AcmeBundle:Organization', 'AcmeBundle\Entity\Organization'],
            ]);

        $this->assertEquals('AcmeBundle\Entity\Organization', $this->provider->getOrganizationClass());
        // test that the class is cached in a local property
        $this->assertEquals('AcmeBundle\Entity\Organization', $this->provider->getOrganizationClass());
    }

    public function testGetMetadataUndefinedClassWithoutCache()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('UndefinedClass'))
            ->willReturn(false);
        $this->configManager->expects($this->never())
            ->method('getEntityConfig');

        $this->entityClassResolver = null;
        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->assertEquals(new OwnershipMetadata(), $this->provider->getMetadata('UndefinedClass'));
    }

    public function testGetMetadataWithoutCache()
    {
        $config = new Config(new EntityConfigId('ownership', \stdClass::class));
        $config->set('owner_type', 'USER');
        $config->set('owner_field_name', 'test_field');
        $config->set('owner_column_name', 'test_column');

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo(\stdClass::class))
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('ownership', $this->equalTo(\stdClass::class))
            ->willReturn($config);

        $this->entityClassResolver = null;
        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->assertEquals(
            new OwnershipMetadata('USER', 'test_field', 'test_column'),
            $this->provider->getMetadata(\stdClass::class)
        );
    }

    public function testGetMetadataSetsOrganizationFieldName()
    {
        $config = new Config(new EntityConfigId('ownership', \stdClass::class));
        $config->set('owner_type', 'ORGANIZATION');
        $config->set('owner_field_name', 'test_field');
        $config->set('owner_column_name', 'test_column');

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo(\stdClass::class))
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('ownership', $this->equalTo(\stdClass::class))
            ->willReturn($config);

        $this->entityClassResolver = null;
        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->assertEquals(
            new OwnershipMetadata('ORGANIZATION', 'test_field', 'test_column', 'test_field', 'test_column'),
            $this->provider->getMetadata(\stdClass::class)
        );
    }

    public function testGetMetadataUndefinedClassWithCache()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('UndefinedClass'))
            ->willReturn(false);
        $this->configManager->expects($this->never())
            ->method('getEntityConfig');

        $this->cache->expects(self::exactly(2))
            ->method('get')
            ->with('UndefinedClass')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function ($cacheKey, $callback) {
                    $item = $this->createMock(ItemInterface::class);
                    return $callback($item);
                }),
                true
            );

        $this->entityClassResolver = null;

        $providerWithCleanLocalCache = clone $this->provider;
        $metadata = new OwnershipMetadata();

        // no cache
        $this->assertEquals($metadata, $this->provider->getMetadata('UndefinedClass'));

        // local cache
        $this->assertEquals($metadata, $this->provider->getMetadata('UndefinedClass'));

        // cache
        $this->assertEquals($metadata, $providerWithCleanLocalCache->getMetadata('UndefinedClass'));
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(?object $user, bool $expectedResult)
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals($expectedResult, $this->provider->supports());
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
    public function testInvalidOwningEntityNames(array $owningEntityNames)
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
    public function testGetMaxAccessLevel(int $accessLevel, ?string $object, int $expectedResult)
    {
        if ($object && $accessLevel === AccessLevel::SYSTEM_LEVEL) {
            $config = new Config(new EntityConfigId('ownership', \stdClass::class));
            $config
                ->set('owner_type', 'USER')
                ->set('owner_field_name', 'test_field')
                ->set('owner_column_name', 'test_column');

            $this->configManager->expects($this->once())
                ->method('hasConfig')
                ->with(\stdClass::class)
                ->willReturn(true);
            $this->configManager->expects($this->once())
                ->method('getEntityConfig')
                ->with('ownership', \stdClass::class)
                ->willReturn($config);
            $this->cache->expects(self::once())
                ->method('get')
                ->willReturnCallback(function ($cacheKey, $callback) {
                    $item = $this->createMock(ItemInterface::class);
                    return $callback($item);
                });
        }

        $this->entityClassResolver = null;

        $this->assertEquals($expectedResult, $this->provider->getMaxAccessLevel($accessLevel, $object));
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
