<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\Testing\Unit\TestContainerBuilder;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OwnershipMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityClassResolver */
    protected $entityClassResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider */
    protected $cache;

    /** @var OwnershipMetadataProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TokenAccessorInterface */
    protected $tokenAccessor;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['fetch', 'save'])
            ->getMockForAbstractClass();

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->container = TestContainerBuilder::create()
            ->add('oro_entity_config.provider.ownership', $this->configProvider)
            ->add('oro_security.owner.ownership_metadata_provider.cache', $this->cache)
            ->add('oro_entity.orm.entity_class_resolver', $this->entityClassResolver)
            ->add('oro_security.token_accessor', $this->tokenAccessor)
            ->getContainer($this);

        $this->provider = new OwnershipMetadataProvider(
            [
                'organization' => 'AcmeBundle:Organization',
                'business_unit' => 'AcmeBundle:BusinessUnit',
                'user' => 'AcmeBundle:User',
            ]
        );
        $this->provider->setContainer($this->container);
    }

    protected function tearDown()
    {
        unset(
            $this->configProvider,
            $this->entityClassResolver,
            $this->cache,
            $this->provider,
            $this->container,
            $this->tokenAccessor
        );
    }

    public function testOwnerClassesConfig()
    {
        $this->entityClassResolver->expects($this->exactly(3))
            ->method('getEntityClass')
            ->will(
                $this->returnValueMap(
                    [
                        ['AcmeBundle:Organization', 'AcmeBundle\Entity\Organization'],
                        ['AcmeBundle:BusinessUnit', 'AcmeBundle\Entity\BusinessUnit'],
                        ['AcmeBundle:User', 'AcmeBundle\Entity\User'],
                    ]
                )
            );

        $provider = new OwnershipMetadataProvider(
            [
                'organization' => 'AcmeBundle:Organization',
                'business_unit' => 'AcmeBundle:BusinessUnit',
                'user' => 'AcmeBundle:User',
            ]
        );
        $provider->setContainer($this->container);

        $this->assertEquals('AcmeBundle\Entity\Organization', $provider->getGlobalLevelClass());
        $this->assertEquals('AcmeBundle\Entity\Organization', $provider->getOrganizationClass());
        $this->assertEquals('AcmeBundle\Entity\BusinessUnit', $provider->getLocalLevelClass());
        $this->assertEquals('AcmeBundle\Entity\BusinessUnit', $provider->getBusinessUnitClass());
        $this->assertEquals('AcmeBundle\Entity\User', $provider->getBasicLevelClass());
        $this->assertEquals('AcmeBundle\Entity\User', $provider->getUserClass());
    }

    public function testGetMetadataUndefinedClassWithoutCache()
    {
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('UndefinedClass'))
            ->willReturn(false);
        $this->configProvider->expects($this->never())
            ->method('getConfig');

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

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo(\stdClass::class))
            ->will($this->returnValue(true));
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($this->equalTo(\stdClass::class))
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

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo(\stdClass::class))
            ->will($this->returnValue(true));
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($this->equalTo(\stdClass::class))
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
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('UndefinedClass'))
            ->will($this->returnValue(false));
        $this->configProvider->expects($this->never())
            ->method('getConfig');

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
     * @expectedExceptionMessage $owningEntityNames must contains `organization`, `business_unit` and `user` keys
     *
     * @param array $owningEntityNames
     */
    public function testSetAccessLevelClassesException(array $owningEntityNames)
    {
        $provider = new OwnershipMetadataProvider($owningEntityNames);
        $provider->setContainer($this->container);
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

            $this->configProvider->expects($this->once())
                ->method('hasConfig')
                ->with(\stdClass::class)
                ->willReturn(true);
            $this->configProvider->expects($this->once())
                ->method('getConfig')
                ->with(\stdClass::class)
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
