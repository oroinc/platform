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

class EntityAliasConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EntityAliasResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityAliasResolver;

    /** @var EntityAliasConfigListener */
    private $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);

        $this->listener = new EntityAliasConfigListener($this->entityAliasResolver);
    }

    public function testPreFlushNewEntityCreated()
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

    public function testPreFlushNewEntityAdded()
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

    public function testPreFlushNoExtendConfig()
    {
        $event = new PreFlushConfigEvent([], $this->configManager);

        $this->entityAliasResolver->expects($this->never())
            ->method('clearCache');

        $this->listener->preFlush($event);
    }

    public function testPreFlushNewFieldCreated()
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
