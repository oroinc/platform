<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider as Provider;

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

        $this->entity = new EntitySecurityMetadata(Provider::ACL_SECURITY_TYPE, 'SomeClass', 'SomeGroup', 'SomeLabel');

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

        $securityConfigId = new EntityConfigId('security', 'SomeClass');
        $securityConfig = new Config($securityConfigId);
        $securityConfig->set('type', Provider::ACL_SECURITY_TYPE);
        $securityConfig->set('permissions', 'All');
        $securityConfig->set('group_name', 'SomeGroup');
        $securityConfig->set('category', '');

        $securityConfigs = array($securityConfig);
        $this->securityConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->will($this->returnValue($securityConfigs));

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
            $this->cache
        );

        $provider->clearCache('SomeType');
        $provider->clearCache();
    }
}
