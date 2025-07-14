<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\EventListener\EntityAliasConfigListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityAliasConfigListenerTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private EntityAliasResolver&MockObject $entityAliasResolver;
    private EntityAliasConfigListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);

        $this->listener = new EntityAliasConfigListener($this->entityAliasResolver);
    }

    public function testPreFlushNewEntityCreated(): void
    {
        $config = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $event = new PreFlushConfigEvent(['extend' => $config], $this->configManager);

        $this->configManager->expects($this->once())
            ->method('getConfigChangeSet')
            ->with($this->identicalTo($config))
            ->willReturn(['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]]);
        $this->entityAliasResolver->expects($this->once())
            ->method('clearCache');

        $this->listener->preFlush($event);
    }

    public function testPreFlushNewEntityAdded(): void
    {
        $config = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $event = new PreFlushConfigEvent(['extend' => $config], $this->configManager);

        $this->configManager->expects($this->once())
            ->method('getConfigChangeSet')
            ->with($this->identicalTo($config))
            ->willReturn(['state' => [null, ExtendScope::STATE_NEW]]);
        $this->entityAliasResolver->expects($this->never())
            ->method('clearCache');

        $this->listener->preFlush($event);
    }

    public function testPreFlushNoExtendConfig(): void
    {
        $event = new PreFlushConfigEvent([], $this->configManager);

        $this->entityAliasResolver->expects($this->never())
            ->method('clearCache');

        $this->listener->preFlush($event);
    }

    public function testPreFlushNewFieldCreated(): void
    {
        $config = new Config(new FieldConfigId('extend', 'Test\Entity', 'field1'));
        $event = new PreFlushConfigEvent(['extend' => $config], $this->configManager);

        $this->configManager->expects($this->never())
            ->method('getConfigChangeSet');
        $this->entityAliasResolver->expects($this->never())
            ->method('clearCache');

        $this->listener->preFlush($event);
    }
}
