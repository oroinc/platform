<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
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

        $this->provider = new OwnershipMetadataProvider(
            [
                'organization' => 'AcmeBundle:Organization',
                'business_unit' => 'AcmeBundle:BusinessUnit',
                'user' => 'AcmeBundle:User',
            ],
            $this->configProvider,
            $this->entityClassResolver,
            $this->cache
        );
    }

    protected function tearDown()
    {
        unset($this->configProvider, $this->entityClassResolver, $this->cache, $this->provider);
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
                        ['AcmeBundle:User', 'AcmeBundle\Entity\User']
                    ]
                )
            );

        $provider = new OwnershipMetadataProvider(
            [
                'organization' => 'AcmeBundle:Organization',
                'business_unit' => 'AcmeBundle:BusinessUnit',
                'user' => 'AcmeBundle:User',
            ],
            $this->configProvider,
            $this->entityClassResolver
        );

        $this->assertEquals('AcmeBundle\Entity\Organization', $provider->getGlobalLevelClass());
        $this->assertEquals('AcmeBundle\Entity\BusinessUnit', $provider->getLocalLevelClass());
        $this->assertEquals('AcmeBundle\Entity\User', $provider->getBasicLevelClass());
    }

    public function testOwnerClassesConfigWithoutEntityClassResolver()
    {
        $provider = new OwnershipMetadataProvider(
            [
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ],
            $this->configProvider
        );

        $this->assertEquals('AcmeBundle\Entity\Organization', $provider->getGlobalLevelClass());
        $this->assertEquals('AcmeBundle\Entity\BusinessUnit', $provider->getLocalLevelClass());
        $this->assertEquals('AcmeBundle\Entity\User', $provider->getBasicLevelClass());
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
     * @param SecurityFacade|null $securityFacade
     * @param bool $expectedResult
     */
    public function testSupports($securityFacade, $expectedResult)
    {
        if ($securityFacade) {
            $this->provider->setSecurityFacade($securityFacade);
        }

        $this->assertEquals($expectedResult, $this->provider->supports());
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        $securityFacade1 = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $securityFacade2 = clone $securityFacade1;

        $securityFacade1->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn(new \stdClass());

        $securityFacade2->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn(new User());

        return [
            'without security facade' => [
                'securityFacade' => null,
                'expectedResult' => false,
            ],
            'security facade with incorrect user class' => [
                'securityFacade' => $securityFacade1,
                'expectedResult' => false,
            ],
            'security facade with user class' => [
                'securityFacade' => $securityFacade2,
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
     * @param array $owningEntityNames
     */
    public function testSetAccessLevelClassesException(array $owningEntityNames)
    {
        if (count($owningEntityNames) !== 3) {
            $this->setExpectedException(
                '\InvalidArgumentException',
                'Array parameter $owningEntityNames must contains `organization`, `business_unit` and `user` keys'
            );
        }

        $this->assertInstanceOf(
            'Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider',
            new OwnershipMetadataProvider($owningEntityNames, $this->configProvider)
        );
    }

    public function owningEntityNamesDataProvider()
    {
        return [
            [
                'owningEntityNames' => []
            ],
            [
                'owningEntityNames' => [
                    'organization' => 'AcmeBundle\Entity\Organization',
                ]
            ],
            [
                'owningEntityNames' => [
                    'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                ]
            ],
            [
                'owningEntityNames' => [
                    'user' => 'AcmeBundle\Entity\User',
                ]
            ],
            [
                'owningEntityNames' => [
                    'organization' => 'AcmeBundle\Entity\Organization',
                    'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                ]
            ],
            [
                'owningEntityNames' => [
                    'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                    'user' => 'AcmeBundle\Entity\User',
                ]
            ],
            [
                'owningEntityNames' => [
                    'organization' => 'AcmeBundle\Entity\Organization',
                    'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                    'user' => 'AcmeBundle\Entity\User',
                ]
            ],
        ];
    }
}
