<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\RenameFieldEvent;
use Oro\Bundle\EntityExtendBundle\EventListener\EntityConfigListener;
use Oro\Bundle\UserBundle\Entity\User;

class EntityConfigListenerCreateFieldTest extends EntityConfigListenerTestCase
{
    private const ENTITY_CLASS_NAME = User::class;

    public function testCreateNewField()
    {
        $entityConfig = new Config(
            new EntityConfigId('extend', self::ENTITY_CLASS_NAME)
        );

        //value of Config should be empty
        $this->assertEmpty($entityConfig->all());

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS_NAME)
            ->willReturn($entityConfig);

        $event = new FieldConfigEvent(self::ENTITY_CLASS_NAME, 'testField', $this->configManager);

        $listener = new EntityConfigListener($this->eventDispatcher);
        $listener->createField($event);

        $this->assertEquals(
            [],
            $this->configManager->getUpdateConfig()
        );
    }

    public function testUpdateNewField()
    {
        $entityConfig = new Config(
            new EntityConfigId('extend', self::ENTITY_CLASS_NAME)
        );
        $entityConfig->set('upgradeable', false);

        $this->assertEquals(
            ['upgradeable' => false],
            $entityConfig->all()
        );

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS_NAME)
            ->willReturn($entityConfig);

        $event = new FieldConfigEvent(self::ENTITY_CLASS_NAME, 'testField', $this->configManager);

        $listener = new EntityConfigListener($this->eventDispatcher);
        $listener->createField($event);

        $this->assertEquals(
            ['upgradeable' => true],
            $entityConfig->all()
        );
        $this->assertEquals(
            [$entityConfig],
            $this->configManager->getUpdateConfig()
        );
    }

    /**
     * Test new index created and old deleted when field renamed
     */
    public function testRenameField()
    {
        $entityConfig = new Config(
            new EntityConfigId('extend', self::ENTITY_CLASS_NAME)
        );
        $entityConfig->set(
            'index',
            [
                'testField' => ['testField'],
            ]
        );

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS_NAME)
            ->willReturn($entityConfig);

        $event = new RenameFieldEvent(self::ENTITY_CLASS_NAME, 'testField', 'newName', $this->configManager);

        $listener = new EntityConfigListener($this->eventDispatcher);
        $listener->renameField($event);

        $this->assertEquals(
            ['newName' => ['testField']],
            $entityConfig->get('index')
        );
    }
}
