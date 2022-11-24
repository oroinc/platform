<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\EntityFieldStateChecker;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class EntityFieldStateCheckerTest extends \PHPUnit\Framework\TestCase
{
    private const SCOPE_EXTEND = 'extend';
    private const CODE_EXTEND_FIRST = 'code_extend_first';
    private const FORM_TYPE_EXTEND = 'form_type_extend';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var EntityFieldStateChecker */
    private $entityFieldStateChecker;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->entityFieldStateChecker = new EntityFieldStateChecker($this->configManager, $this->formFactory);
    }

    public function testIsSchemaUpdateNeededWhenNoProviders()
    {
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);
        $this->configManager->expects($this->once())
            ->method('getProviders')
            ->willReturn([]);

        $this->assertFalse($this->entityFieldStateChecker->isSchemaUpdateNeeded($fieldConfigModel));
    }

    public function testIsSchemaUpdateNeededWhenNoChangeSet()
    {
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);

        $extendPropertyConfig = $this->createMock(PropertyConfigContainer::class);

        $this->configManager->expects($this->once())
            ->method('getProviders')
            ->willReturn([$this->configureProvider(self::SCOPE_EXTEND, $extendPropertyConfig)]);

        $extendConfigId = $this->createMock(ConfigIdInterface::class);
        $this->configManager->expects($this->once())
            ->method('getConfigIdByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendConfigId);

        $newFieldConfig = new Config($extendConfigId, []);
        $this->configManager->expects($this->once())
            ->method('getConfig')
            ->with($extendConfigId)
            ->willReturn($newFieldConfig);

        $oldFieldConfig = new Config($extendConfigId, []);
        $this->configManager->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($oldFieldConfig);

        $this->assertFalse($this->entityFieldStateChecker->isSchemaUpdateNeeded($fieldConfigModel));
    }

    public function testIsSchemaUpdateNeededWhenNoSchemaUpdateRequiredForCode()
    {
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);

        $extendPropertyConfig = $this->createMock(PropertyConfigContainer::class);
        $extendPropertyConfig->expects($this->once())
            ->method('isSchemaUpdateRequired')
            ->with(self::CODE_EXTEND_FIRST, PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(false);

        $this->configManager->expects($this->once())
            ->method('getProviders')
            ->willReturn([$this->configureProvider(self::SCOPE_EXTEND, $extendPropertyConfig)]);

        $extendConfigId = $this->createMock(ConfigIdInterface::class);
        $this->configManager->expects($this->once())
            ->method('getConfigIdByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendConfigId);

        $oldFieldConfig = new Config($extendConfigId, [self::CODE_EXTEND_FIRST => 1]);
        $this->configManager->expects($this->once())
            ->method('getConfig')
            ->with($extendConfigId)
            ->willReturn($oldFieldConfig);

        $newFieldConfig = new Config($extendConfigId, [self::CODE_EXTEND_FIRST => 2]);
        $this->configManager->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($newFieldConfig);

        $this->assertFalse($this->entityFieldStateChecker->isSchemaUpdateNeeded($fieldConfigModel));
    }

    public function testIsSchemaUpdateNeededWhenNoFormItemsForCode()
    {
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);

        $extendPropertyConfig = $this->createMock(PropertyConfigContainer::class);
        $extendPropertyConfig->expects($this->once())
            ->method('isSchemaUpdateRequired')
            ->with(self::CODE_EXTEND_FIRST, PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(true);

        $extendPropertyConfig->expects($this->once())
            ->method('getFormItems')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn([]);

        $this->configManager->expects($this->once())
            ->method('getProviders')
            ->willReturn([$this->configureProvider(self::SCOPE_EXTEND, $extendPropertyConfig)]);

        $extendConfigId = $this->createMock(ConfigIdInterface::class);
        $this->configManager->expects($this->once())
            ->method('getConfigIdByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendConfigId);

        $oldFieldConfig = new Config($extendConfigId, [self::CODE_EXTEND_FIRST => 1]);
        $this->configManager->expects($this->once())
            ->method('getConfig')
            ->with($extendConfigId)
            ->willReturn($oldFieldConfig);

        $newFieldConfig = new Config($extendConfigId, [self::CODE_EXTEND_FIRST => 2]);
        $this->configManager->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($newFieldConfig);

        $this->assertFalse($this->entityFieldStateChecker->isSchemaUpdateNeeded($fieldConfigModel));
    }

    public function testIsSchemaUpdateNeededWhenNoUpdateNeededByCallback()
    {
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);

        $extendPropertyConfig = $this->createMock(PropertyConfigContainer::class);
        $extendPropertyConfig->expects($this->once())
            ->method('isSchemaUpdateRequired')
            ->with(self::CODE_EXTEND_FIRST, PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(true);

        $extendPropertyConfig->expects($this->once())
            ->method('getFormItems')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn([self::CODE_EXTEND_FIRST => [
                'form' => [
                    'type' => self::FORM_TYPE_EXTEND,
                    'options' => []
                ]
            ]]);

        $this->configManager->expects($this->once())
            ->method('getProviders')
            ->willReturn([$this->configureProvider(self::SCOPE_EXTEND, $extendPropertyConfig)]);

        $extendConfigId = $this->createMock(ConfigIdInterface::class);
        $this->configManager->expects($this->once())
            ->method('getConfigIdByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendConfigId);

        $oldFieldConfig = new Config($extendConfigId, [self::CODE_EXTEND_FIRST => 1]);
        $this->configManager->expects($this->once())
            ->method('getConfig')
            ->with($extendConfigId)
            ->willReturn($oldFieldConfig);

        $newFieldConfig = new Config($extendConfigId, [self::CODE_EXTEND_FIRST => 2]);
        $this->configManager->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($newFieldConfig);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('schema_update_required')
            ->willReturn(function () {
                return false;
            });

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(self::FORM_TYPE_EXTEND, null, ['config_id' => $extendConfigId])
            ->willReturn($form);

        $this->assertFalse($this->entityFieldStateChecker->isSchemaUpdateNeeded($fieldConfigModel));
    }

    public function testIsSchemaUpdate()
    {
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);

        $extendPropertyConfig = $this->createMock(PropertyConfigContainer::class);
        $extendPropertyConfig->expects($this->once())
            ->method('isSchemaUpdateRequired')
            ->with(self::CODE_EXTEND_FIRST, PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(true);

        $extendPropertyConfig->expects($this->once())
            ->method('getFormItems')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn([self::CODE_EXTEND_FIRST => [
                'form' => [
                    'type' => self::FORM_TYPE_EXTEND,
                    'options' => []
                ]
            ]]);

        $this->configManager->expects($this->once())
            ->method('getProviders')
            ->willReturn([$this->configureProvider(self::SCOPE_EXTEND, $extendPropertyConfig)]);

        $extendConfigId = $this->createMock(ConfigIdInterface::class);
        $this->configManager->expects($this->once())
            ->method('getConfigIdByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendConfigId);

        $oldFieldConfig = new Config($extendConfigId, [self::CODE_EXTEND_FIRST => 1]);
        $this->configManager->expects($this->once())
            ->method('getConfig')
            ->with($extendConfigId)
            ->willReturn($oldFieldConfig);

        $newFieldConfig = new Config($extendConfigId, [self::CODE_EXTEND_FIRST => 2]);
        $this->configManager->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($newFieldConfig);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('schema_update_required')
            ->willReturn(function () {
                return true;
            });

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(self::FORM_TYPE_EXTEND, null, ['config_id' => $extendConfigId])
            ->willReturn($form);

        $this->assertTrue($this->entityFieldStateChecker->isSchemaUpdateNeeded($fieldConfigModel));
    }

    private function configureProvider(string $scope, PropertyConfigContainer $propertyConfig): ConfigProvider
    {
        $provider = $this->createMock(ConfigProvider::class);
        $provider->expects($this->any())
            ->method('getScope')
            ->willReturn($scope);
        $provider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfig);

        return $provider;
    }
}
