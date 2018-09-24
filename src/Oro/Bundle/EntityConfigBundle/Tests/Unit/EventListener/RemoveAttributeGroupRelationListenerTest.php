<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\EventListener\RemoveAttributeGroupRelationListener;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ReflectionUtil;

class RemoveAttributeGroupRelationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var RemoveAttributeGroupRelationListener */
    private $listener;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new RemoveAttributeGroupRelationListener($this->doctrine);
    }

    public function testOnPostFlushConfigShouldRemoveAttributeGroupRelationForRemovedAttribute()
    {
        $entityClass = 'Test\Entity';
        $fieldName = 'testField';
        $fieldModelId = 123;
        $repository = $this->createMock(AttributeGroupRelationRepository::class);

        $entityModel = new EntityConfigModel($entityClass);
        $fieldModel = new FieldConfigModel($fieldName);
        $fieldModel->setEntity($entityModel);
        ReflectionUtil::setId($fieldModel, $fieldModelId);
        $fieldModel->fromArray('attribute', ['is_attribute' => true]);

        $this->configManager->expects($this->once())
            ->method('getFieldConfigChangeSet')
            ->with('extend', $entityClass, $fieldName)
            ->willReturn(['is_deleted' => [false, true]]);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(AttributeGroupRelation::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('removeByFieldId')
            ->with($fieldModelId);

        $event = new PostFlushConfigEvent([$fieldModel], $this->configManager);
        $this->listener->onPostFlushConfig($event);
    }

    public function testOnPostFlushConfigShouldDoNothingForRemovedField()
    {
        $entityClass = 'Test\Entity';
        $fieldName = 'testField';
        $fieldModelId = 123;

        $entityModel = new EntityConfigModel($entityClass);
        $fieldModel = new FieldConfigModel($fieldName);
        $fieldModel->setEntity($entityModel);
        ReflectionUtil::setId($fieldModel, $fieldModelId);

        $this->configManager->expects($this->once())
            ->method('getFieldConfigChangeSet')
            ->with('extend', $entityClass, $fieldName)
            ->willReturn(['is_deleted' => [false, true]]);

        $this->doctrine->expects($this->never())
            ->method('getRepository');

        $event = new PostFlushConfigEvent([$fieldModel], $this->configManager);
        $this->listener->onPostFlushConfig($event);
    }

    public function testOnPostFlushConfigShouldDoNothingForRestoredAttribute()
    {
        $entityClass = 'Test\Entity';
        $fieldName = 'testField';
        $fieldModelId = 123;

        $entityModel = new EntityConfigModel($entityClass);
        $fieldModel = new FieldConfigModel($fieldName);
        $fieldModel->setEntity($entityModel);
        ReflectionUtil::setId($fieldModel, $fieldModelId);
        $fieldModel->fromArray('attribute', ['is_attribute' => true]);

        $this->configManager->expects($this->once())
            ->method('getFieldConfigChangeSet')
            ->with('extend', $entityClass, $fieldName)
            ->willReturn(['is_deleted' => [true, false]]);

        $this->doctrine->expects($this->never())
            ->method('getRepository');

        $event = new PostFlushConfigEvent([$fieldModel], $this->configManager);
        $this->listener->onPostFlushConfig($event);
    }

    public function testOnPostFlushConfigShouldDoNothingForChangedEntityConfig()
    {
        $entityClass = 'Test\Entity';

        $entityModel = new EntityConfigModel($entityClass);

        $this->configManager->expects($this->never())
            ->method('getFieldConfigChangeSet');

        $this->doctrine->expects($this->never())
            ->method('getRepository');

        $event = new PostFlushConfigEvent([$entityModel], $this->configManager);
        $this->listener->onPostFlushConfig($event);
    }
}
