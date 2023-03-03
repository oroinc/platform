<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\EntityConfigEvent;
use Oro\Bundle\EntityExtendBundle\EventListener\EntityConfigListener;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass2;

class EntityConfigListenerCreateEntityTest extends EntityConfigListenerTestCase
{
    /**
     * Test class is extend and persisted
     */
    public function testNewExtendEntity()
    {
        $configModel = new EntityConfigModel(TestClass::class);
        $entityConfig = new Config(new EntityConfigId('extend', TestClass::class));

        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($entityConfig);

        $event = new EntityConfigEvent($configModel->getClassName(), $this->configManager);

        $listener = new EntityConfigListener($this->eventDispatcher);
        $listener->updateEntity($event);

        $this->assertEquals(
            [
                'is_extend' => true,
            ],
            $entityConfig->all()
        );

        $this->assertEquals(
            [$entityConfig],
            $this->configManager->getUpdateConfig()
        );
    }

    /**
     * Test class is NOT extend and should NOT be persisted
     */
    public function testNewNotExtendEntity()
    {
        $configModel = new EntityConfigModel(TestClass2::class);
        $entityConfig = new Config(new EntityConfigId(TestClass2::class, 'extend'));
        $entityConfig->set('is_extend', true);

        /**
         * value of NEW Config should be empty
         */
        $this->assertEquals(
            ['is_extend' => true],
            $entityConfig->all()
        );

        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($entityConfig);

        $event = new EntityConfigEvent($configModel->getClassName(), $this->configManager);

        $listener = new EntityConfigListener($this->eventDispatcher);
        $listener->updateEntity($event);

        $this->assertEquals(
            [],
            $this->configManager->getUpdateConfig()
        );
    }
}
