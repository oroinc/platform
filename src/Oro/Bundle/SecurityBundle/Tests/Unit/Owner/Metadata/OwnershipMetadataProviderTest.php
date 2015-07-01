<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OwnershipMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider
     */
    protected $cache;

    /**
     * @var OwnershipMetadataProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

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

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'oro_entity_config.provider.ownership',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->configProvider,
                        ],
                        [
                            'oro_security.owner.ownership_metadata_provider.cache',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->cache,
                        ],
                        [
                            'oro_entity.orm.entity_class_resolver',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->entityClassResolver,
                        ],
                        [
                            'oro_security.security_facade',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->securityFacade,
                        ],
                    ]
                )
            );

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
            $this->securityFacade
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
        $config = new Config(new EntityConfigId('ownership', 'SomeClass'));
        $config->set('owner_type', 'USER');
        $config->set('owner_field_name', 'test_field');
        $config->set('owner_column_name', 'test_column');

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('SomeClass'))
            ->will($this->returnValue(true));
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($this->equalTo('SomeClass'))
            ->will($this->returnValue($config));

        $this->entityClassResolver = null;
        $this->cache = null;

        $this->assertEquals(
            new OwnershipMetadata('USER', 'test_field', 'test_column'),
            $this->provider->getMetadata('SomeClass')
        );
    }

    public function testGetMetadataSetsOrganizationFieldName()
    {
        $config = new Config(new EntityConfigId('ownership', 'SomeClass'));
        $config->set('owner_type', 'ORGANIZATION');
        $config->set('owner_field_name', 'test_field');
        $config->set('owner_column_name', 'test_column');

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('SomeClass'))
            ->will($this->returnValue(true));
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($this->equalTo('SomeClass'))
            ->will($this->returnValue($config));

        $this->entityClassResolver = null;
        $this->cache = null;

        $this->assertEquals(
            new OwnershipMetadata('ORGANIZATION', 'test_field', 'test_column', 'test_field', 'test_column'),
            $this->provider->getMetadata('SomeClass')
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
        $this->securityFacade
            ->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->assertEquals($expectedResult, $this->provider->supports());
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'without security facade' => [
                'securityFacade' => null,
                'expectedResult' => false,
            ],
            'security facade with incorrect user class' => [
                'securityFacade' => new \stdClass(),
                'expectedResult' => false,
            ],
            'security facade with user class' => [
                'securityFacade' => new User(),
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method getSystemLevelClass() unsupported.
     */
    public function testGetSystemLevelClass()
    {
        $this->assertFalse($this->provider->getSystemLevelClass());
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
            $config = new Config(new EntityConfigId('ownership', 'SomeClass'));
            $config
                ->set('owner_type', 'USER')
                ->set('owner_field_name', 'test_field')
                ->set('owner_column_name', 'test_column');

            $this->configProvider->expects($this->once())
                ->method('hasConfig')
                ->with('SomeClass')
                ->willReturn(true);
            $this->configProvider->expects($this->once())
                ->method('getConfig')
                ->with('SomeClass')
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
                'object' => 'SomeClass',
                'expectedResult' => AccessLevel::GLOBAL_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::GLOBAL_LEVEL,
                'object' => null,
                'expectedResult' => AccessLevel::GLOBAL_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::DEEP_LEVEL,
                'object' => 'SomeClass',
                'expectedResult' => AccessLevel::DEEP_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::DEEP_LEVEL,
                'object' => null,
                'expectedResult' => AccessLevel::DEEP_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::LOCAL_LEVEL,
                'object' => 'SomeClass',
                'expectedResult' => AccessLevel::LOCAL_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::LOCAL_LEVEL,
                'object' => null,
                'expectedResult' => AccessLevel::LOCAL_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::BASIC_LEVEL,
                'object' => 'SomeClass',
                'expectedResult' => AccessLevel::BASIC_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::BASIC_LEVEL,
                'object' => null,
                'expectedResult' => AccessLevel::BASIC_LEVEL
            ],
            [
                'accessLevel' => AccessLevel::SYSTEM_LEVEL,
                'object' => 'SomeClass',
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
