<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\EntityConfigEvent;
use Oro\Bundle\EntityExtendBundle\EventListener\EntityConfigListener;

class EntityConfigListenerCreateEntityTest extends EntityConfigListenerTestCase
{
    /**
     * Test class is extend and persisted
     */
    public function testNewExtendEntity()
    {
        $configModel = new EntityConfigModel(
            'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass'
        );
        $entityConfig = new Config(
            new EntityConfigId(
                'extend',
                'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass'
            )
        );

        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($entityConfig));

        $event = new EntityConfigEvent($configModel->getClassName(), $this->configManager);

        $listener = new EntityConfigListener($this->eventDispatcher);
        $listener->updateEntity($event);

        $this->assertEquals(
            [
                'is_extend' => true,
                'extend_class' => 'Extend\Entity\EX_OroEntityExtendBundle_Tests_Unit_Fixtures_TestClass'
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
        $configModel = new EntityConfigModel(
            'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass2'
        );
        $entityConfig = new Config(
            new EntityConfigId(
                'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass2',
                'extend'
            )
        );

        /**
         * value of NEW Config should be empty
         */
        $this->assertEquals(
            [],
            $entityConfig->all()
        );

        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($entityConfig));

        $event = new EntityConfigEvent($configModel->getClassName(), $this->configManager);

        $listener = new EntityConfigListener($this->eventDispatcher);
        $listener->updateEntity($event);

        $this->assertEquals(
            [],
            $this->configManager->getUpdateConfig()
        );
    }
}
