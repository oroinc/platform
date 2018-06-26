<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OwnershipMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityClassResolver */
    protected $entityClassResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheProvider */
    protected $cache;

    /** @var OwnershipMetadataProvider */
    protected $provider;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->cache = $this->createMock(CacheProvider::class);

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
        $this->cache = null;

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
            ->will($this->returnValue(true));
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('ownership', $this->equalTo(\stdClass::class))
            ->will($this->returnValue($config));

        $this->entityClassResolver = null;
        $this->cache = null;

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
            ->will($this->returnValue(true));
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('ownership', $this->equalTo(\stdClass::class))
            ->will($this->returnValue($config));

        $this->entityClassResolver = null;
        $this->cache = null;

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
            ->will($this->returnValue(false));
        $this->configManager->expects($this->never())
            ->method('getEntityConfig');

        $this->cache->expects($this->at(0))
            ->method('fetch')
            ->with($this->equalTo('UndefinedClass'))
            ->will($this->returnValue(false));
        $this->cache->expects($this->at(2))
            ->method('fetch')
            ->with($this->equalTo('UndefinedClass'))
            ->will($this->returnValue(true));
        $this->cache->expects($this->once())
            ->method('save')
            ->with($this->equalTo('UndefinedClass'), $this->equalTo(true));

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
     *
     * @param mixed $user
     * @param bool $expectedResult
     */
    public function testSupports($user, $expectedResult)
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals($expectedResult, $this->provider->supports());
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
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
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The $owningEntityNames must contains "organization", "business_unit" and "user" keys.
     *
     * @param array $owningEntityNames
     */
    public function testInvalidOwningEntityNames(array $owningEntityNames)
    {
        $provider = new OwnershipMetadataProvider(
            $owningEntityNames,
            $this->configManager,
            $this->entityClassResolver,
            $this->tokenAccessor,
            $this->cache
        );
        $provider->getUserClass();
    }

    /**
     * @return array
     */
    public function owningEntityNamesDataProvider()
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
     *
     * @param $accessLevel
     * @param $object
     * @param $expectedResult
     */
    public function testGetMaxAccessLevel($accessLevel, $object, $expectedResult)
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
        }

        $this->entityClassResolver = null;
        $this->cache = null;

        $this->assertEquals($expectedResult, $this->provider->getMaxAccessLevel($accessLevel, $object));
    }

    /**
     * @return array
     */
    public function getMaxAccessLevelDataProvider()
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
