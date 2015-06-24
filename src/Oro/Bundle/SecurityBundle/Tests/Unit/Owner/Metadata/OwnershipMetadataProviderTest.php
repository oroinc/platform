<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class OwnershipMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $configProvider;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->configProvider);
    }

    public function testOwnerClassesConfig()
    {
        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolver->expects($this->exactly(3))
            ->method('getEntityClass')
            ->will(
                $this->returnValueMap(
                    array(
                        array('AcmeBundle:Organization', 'AcmeBundle\Entity\Organization'),
                        array('AcmeBundle:BusinessUnit', 'AcmeBundle\Entity\BusinessUnit'),
                        array('AcmeBundle:User', 'AcmeBundle\Entity\User'),
                    )
                )
            );

        $provider = new OwnershipMetadataProvider(
            array(
                'organization' => 'AcmeBundle:Organization',
                'business_unit' => 'AcmeBundle:BusinessUnit',
                'user' => 'AcmeBundle:User',
            ),
            $this->configProvider,
            $entityClassResolver
        );

        $this->assertEquals('AcmeBundle\Entity\Organization', $provider->getOrganizationClass());
        $this->assertEquals('AcmeBundle\Entity\BusinessUnit', $provider->getBusinessUnitClass());
        $this->assertEquals('AcmeBundle\Entity\User', $provider->getUserClass());
    }

    public function testOwnerClassesConfigWithoutEntityClassResolver()
    {
        $provider = new OwnershipMetadataProvider(
            array(
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ),
            $this->configProvider
        );

        $this->assertEquals('AcmeBundle\Entity\Organization', $provider->getOrganizationClass());
        $this->assertEquals('AcmeBundle\Entity\BusinessUnit', $provider->getBusinessUnitClass());
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

        $provider = new OwnershipMetadataProvider(
            array(
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ),
            $this->configProvider,
            null
        );

        $this->assertEquals(new OwnershipMetadata(), $provider->getMetadata('UndefinedClass'));
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

        $provider = new OwnershipMetadataProvider(
            array(
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ),
            $this->configProvider,
            null
        );

        $this->assertEquals(
            new OwnershipMetadata('USER', 'test_field', 'test_column'),
            $provider->getMetadata('SomeClass')
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

        $provider = new OwnershipMetadataProvider(
            array(
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ),
            $this->configProvider,
            null
        );

        $this->assertEquals(
            new OwnershipMetadata('ORGANIZATION', 'test_field', 'test_column', 'test_field', 'test_column'),
            $provider->getMetadata('SomeClass')
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

        $cache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\CacheProvider',
            array(),
            '',
            false,
            true,
            true,
            array('fetch', 'save')
        );

        $provider = new OwnershipMetadataProvider(
            array(
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ),
            $this->configProvider,
            null,
            $cache
        );

        $metadata = new OwnershipMetadata();

        $cache->expects($this->at(0))
            ->method('fetch')
            ->with($this->equalTo('UndefinedClass'))
            ->will($this->returnValue(false));
        $cache->expects($this->at(2))
            ->method('fetch')
            ->with($this->equalTo('UndefinedClass'))
            ->will($this->returnValue(true));
        $cache->expects($this->once())
            ->method('save')
            ->with($this->equalTo('UndefinedClass'), $this->equalTo(true));

        // no cache
        $this->assertEquals(
            $metadata,
            $provider->getMetadata('UndefinedClass')
        );

        // local cache
        $this->assertEquals(
            $metadata,
            $provider->getMetadata('UndefinedClass')
        );

        // cache
        $provider = new OwnershipMetadataProvider(
            array(
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ),
            $this->configProvider,
            null,
            $cache
        );
        $this->assertEquals(
            $metadata,
            $provider->getMetadata('UndefinedClass')
        );
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param SecurityFacade|null $securityFacade
     * @param bool $expectedResult
     */
    public function testSupports($securityFacade, $expectedResult)
    {
        $provider = new OwnershipMetadataProvider(
            array(
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ),
            $this->configProvider
        );
        $provider->setSecurityFacade($securityFacade);

        $this->assertEquals($expectedResult, $provider->supports());
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
}
