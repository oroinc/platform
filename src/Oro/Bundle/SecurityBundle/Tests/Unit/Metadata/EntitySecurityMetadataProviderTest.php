<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider as Provider;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;

class EntitySecurityMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var EntitySecurityMetadata */
    protected $entity;

    /**
     * @var Config
     */
    protected $extendConfig;

    protected function setUp()
    {
        $this->securityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendConfig = new Config(new EntityConfigId('extend', 'SomeClass'));
        $this->extendConfig->set('state', ExtendScope::STATE_ACTIVE);
        $this->cache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\CacheProvider',
            array(),
            '',
            false,
            true,
            true,
            array('fetch', 'save', 'delete', 'deleteAll')
        );

        $this->doctrine = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity = new EntitySecurityMetadata(
            Provider::ACL_SECURITY_TYPE,
            'SomeClass',
            'SomeGroup',
            'SomeLabel',
            [],
            '',
            '',
            [
                'firstName' => new FieldSecurityMetadata('firstName', 'someclass.first_name.label', ['VIEW', 'CREATE']),
                'lastName' => new FieldSecurityMetadata('lastName', 'someclass.last_name.label', []),
                'cityName' => new FieldSecurityMetadata('cityName', 'someclass.city_name.label', [])
            ]
        );

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->extendConfig));
    }

    public function testIsProtectedEntity()
    {
        $this->cache->expects($this->any())
            ->method('fetch')
            ->with(Provider::ACL_SECURITY_TYPE)
            ->will($this->returnValue(array('SomeClass' => new EntitySecurityMetadata())));

        $provider = new Provider(
            $this->securityConfigProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->doctrine,
            $this->cache
        );

        $this->assertTrue($provider->isProtectedEntity('SomeClass'));
        $this->assertFalse($provider->isProtectedEntity('UnknownClass'));
    }

    public function testGetEntities()
    {
        $entityConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $entityConfig->expects($this->at(0))
            ->method('get')
            ->with('label')
            ->will($this->returnValue('SomeLabel'));

        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('SomeClass')
            ->will($this->returnValue(true));
        $this->entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('SomeClass')
            ->will($this->returnValue($entityConfig));

        $this->setTestConfig();

        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()->getMock();
        $metadataFactory = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()->getMock();
        $manager->expects($this->any())->method('getMetadataFactory')->willReturn($metadataFactory);
        $metadata = new ClassMetadata('SomeClass');
        $metadata->identifier = ['id'];
        $metadataFactory->expects($this->any())->method('getMetadataFor')->willReturn($metadata);
        $this->doctrine->expects($this->any())->method('getManagerForClass')->willReturn($manager);

        $this->cache->expects($this->at(0))
            ->method('fetch')
            ->with(Provider::ACL_SECURITY_TYPE)
            ->will($this->returnValue(false));
        $this->cache->expects($this->at(2))
            ->method('fetch')
            ->with(Provider::ACL_SECURITY_TYPE)
            ->will($this->returnValue(array('SomeClass' => $this->entity)));
        $this->cache->expects($this->once())
            ->method('save')
            ->with(Provider::ACL_SECURITY_TYPE, array('SomeClass' => $this->entity));

        $provider = new Provider(
            $this->securityConfigProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->doctrine,
            $this->cache
        );

        // call without cache
        $result = $provider->getEntities();
        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf('Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata', $result);
        $this->assertEquals(serialize($result), serialize(array($this->entity)));

        // call with local cache
        $result = $provider->getEntities();
        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf('Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata', $result);
        $this->assertEquals(serialize($result), serialize(array($this->entity)));

        // call with cache
        $provider = new Provider(
            $this->securityConfigProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->doctrine,
            $this->cache
        );
        $result = $provider->getEntities();
        $this->assertCount(1, $result);
        $this->assertContains($this->entity, $result);
    }

    public function testClearCache()
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('SomeType');

        $this->cache->expects($this->once())
            ->method('deleteAll');

        $provider = new Provider(
            $this->securityConfigProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->doctrine,
            $this->cache
        );

        $provider->clearCache('SomeType');
        $provider->clearCache();
    }

    protected function setTestConfig()
    {
        $securityConfigId = new EntityConfigId('security', 'SomeClass');
        $securityConfig = new Config($securityConfigId);
        $securityConfig->set('type', Provider::ACL_SECURITY_TYPE);
        $securityConfig->set('permissions', 'All');
        $securityConfig->set('group_name', 'SomeGroup');
        $securityConfig->set('category', '');
        $securityConfig->set('field_acl_supported', true);
        $securityConfig->set('field_acl_enabled', true);

        $securityConfigs = array($securityConfig);

        $idFieldConfigId = new FieldConfigId('security', 'SomeClass', 'id');
        $idFieldConfig = new Config($idFieldConfigId);

        $firstNameConfigId = new FieldConfigId('security', 'SomeClass', 'firstName');
        $firstNameFieldConfig = new Config($firstNameConfigId);
        $firstNameFieldConfig->set('permissions', 'VIEW;CREATE');

        $lastNameConfigId = new FieldConfigId('security', 'SomeClass', 'lastName');
        $lastNameFieldConfig = new Config($lastNameConfigId);
        $lastNameFieldConfig->set('permissions', 'All');

        $cityNameConfigId = new FieldConfigId('security', 'SomeClass', 'cityName');
        $cityNameFieldConfig = new Config($cityNameConfigId);

        $fieldsConfig = [$idFieldConfig, $firstNameFieldConfig, $lastNameFieldConfig, $cityNameFieldConfig];

        $this->securityConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->willReturnMap(
                [
                    [null, false, $securityConfigs],
                    ['SomeClass', false, $fieldsConfig]
                ]
            );
    }
}
