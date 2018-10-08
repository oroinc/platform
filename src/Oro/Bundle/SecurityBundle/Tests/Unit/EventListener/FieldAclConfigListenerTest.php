<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\SecurityBundle\EventListener\FieldAclConfigListener;

class FieldAclConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FieldAclConfigListener */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $metadataProvider;


    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new FieldAclConfigListener($this->metadataProvider);
    }

    public function testPreFlushOnFieldConfig()
    {
        $configId = new FieldConfigId('extend', 'test', 'testField', 'string');
        $config = new Config($configId, []);

        $securityConfigId = new FieldConfigId('security', 'test', 'testField', 'string');
        $securityConfig = new Config($securityConfigId, []);

        $event = new PreFlushConfigEvent(['extend' => $config, 'security' => $securityConfig], $this->configManager);

        $this->listener->preFlush($event);

        $this->assertNull($securityConfig->get('field_acl_supported'));
    }


    public function testPreFlushOnSystemEntity()
    {
        $configId = new EntityConfigId('extend', 'test');
        $config = new Config($configId, []);

        $securityConfigId = new EntityConfigId('security', 'test');
        $securityConfig = new Config($securityConfigId, []);

        $event = new PreFlushConfigEvent(['extend' => $config, 'security' => $securityConfig], $this->configManager);

        $this->listener->preFlush($event);

        $this->assertNull($securityConfig->get('field_acl_supported'));
    }

    public function testPreFlushOnCustomEntity()
    {
        $configId = new EntityConfigId('extend', 'test');
        $config = new Config($configId, ['owner' => 'Custom']);

        $securityConfigId = new EntityConfigId('security', 'test');
        $securityConfig = new Config($securityConfigId, []);

        $event = new PreFlushConfigEvent(['extend' => $config, 'security' => $securityConfig], $this->configManager);

        $this->listener->preFlush($event);

        $this->assertTrue($securityConfig->get('field_acl_supported'));
    }

    public function testPreFlushOnNonSecurityProtectedCustomEntity()
    {
        $configId = new EntityConfigId('extend', 'test');
        $config = new Config($configId, ['owner' => 'Custom']);

        $event = new PreFlushConfigEvent(['extend' => $config], $this->configManager);

        $this->listener->preFlush($event);

        $this->assertCount(1, $event->getConfigs());
    }
}
