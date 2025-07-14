<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\SecurityBundle\EventListener\FieldAclConfigListener;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldAclConfigListenerTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private EntitySecurityMetadataProvider&MockObject $metadataProvider;
    private FieldAclConfigListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->metadataProvider = $this->createMock(EntitySecurityMetadataProvider::class);

        $this->listener = new FieldAclConfigListener($this->metadataProvider);
    }

    public function testPreFlushOnFieldConfig(): void
    {
        $configId = new FieldConfigId('extend', 'test', 'testField', 'string');
        $config = new Config($configId, []);

        $securityConfigId = new FieldConfigId('security', 'test', 'testField', 'string');
        $securityConfig = new Config($securityConfigId, []);

        $event = new PreFlushConfigEvent(['extend' => $config, 'security' => $securityConfig], $this->configManager);

        $this->listener->preFlush($event);

        $this->assertNull($securityConfig->get('field_acl_supported'));
    }

    public function testPreFlushOnSystemEntity(): void
    {
        $configId = new EntityConfigId('extend', 'test');
        $config = new Config($configId, []);

        $securityConfigId = new EntityConfigId('security', 'test');
        $securityConfig = new Config($securityConfigId, []);

        $event = new PreFlushConfigEvent(['extend' => $config, 'security' => $securityConfig], $this->configManager);

        $this->listener->preFlush($event);

        $this->assertNull($securityConfig->get('field_acl_supported'));
    }

    public function testPreFlushOnCustomEntity(): void
    {
        $configId = new EntityConfigId('extend', 'test');
        $config = new Config($configId, ['owner' => 'Custom']);

        $securityConfigId = new EntityConfigId('security', 'test');
        $securityConfig = new Config($securityConfigId, []);

        $event = new PreFlushConfigEvent(['extend' => $config, 'security' => $securityConfig], $this->configManager);

        $this->listener->preFlush($event);

        $this->assertTrue($securityConfig->get('field_acl_supported'));
    }

    public function testPreFlushOnNonSecurityProtectedCustomEntity(): void
    {
        $configId = new EntityConfigId('extend', 'test');
        $config = new Config($configId, ['owner' => 'Custom']);

        $event = new PreFlushConfigEvent(['extend' => $config], $this->configManager);

        $this->listener->preFlush($event);

        $this->assertCount(1, $event->getConfigs());
    }
}
