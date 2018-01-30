<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\EntityFieldStateChecker;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class EntityFieldStateCheckerTest extends \PHPUnit_Framework_TestCase
{
    const SCOPE_EXTEND = 'extend';
    const SCOPE_DATAGRID = 'datagrid';
    const CODE_EXTEND_FIRST = 'code_extend_first';
    const FORM_TYPE_EXTEND = 'form_type_extend';

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formFactory;

    /**
     * @var EntityFieldStateChecker
     */
    protected $entityFieldStateChecker;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->entityFieldStateChecker = new EntityFieldStateChecker($this->configManager, $this->formFactory);
    }

    public function testIsSchemaUpdateNeededWhenNoProviders()
    {
        /** @var FieldConfigModel|\PHPUnit_Framework_MockObject_MockObject $fieldConfigModel **/
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);
        $this->configManager
            ->expects($this->once())
            ->method('getProviders')
            ->willReturn([]);

        $this->assertFalse($this->entityFieldStateChecker->isSchemaUpdateNeeded($fieldConfigModel));
    }

    public function testIsSchemaUpdateNeededWhenNoChangeSet()
    {
        /** @var FieldConfigModel|\PHPUnit_Framework_MockObject_MockObject $fieldConfigModel **/
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);

        /** @var PropertyConfigContainer $extendPropertyConfig */
        $extendPropertyConfig = $this->createMock(PropertyConfigContainer::class);

        $this->configManager
            ->expects($this->once())
            ->method('getProviders')
            ->willReturn([$this->configureProvider(self::SCOPE_EXTEND, $extendPropertyConfig)]);

        $extendConfigId = $this->createMock(ConfigIdInterface::class);
        $this->configManager
            ->expects($this->once())
            ->method('getConfigIdByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendConfigId);

        $this->configureChangeSet([]);
        $extendFieldConfig = $this->createMock(ConfigInterface::class);
        $this->configManager
            ->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendFieldConfig);

        $this->configManager
            ->expects($this->once())
            ->method('getConfigChangeSet')
            ->with($extendFieldConfig)
            ->willReturn([]);

        $this->assertFalse($this->entityFieldStateChecker->isSchemaUpdateNeeded($fieldConfigModel));
    }

    public function testIsSchemaUpdateNeededWhenNoSchemaUpdateRequiredForCode()
    {
        /** @var FieldConfigModel|\PHPUnit_Framework_MockObject_MockObject $fieldConfigModel **/
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);

        /** @var PropertyConfigContainer|\PHPUnit_Framework_MockObject_MockObject $extendPropertyConfig */
        $extendPropertyConfig = $this->createMock(PropertyConfigContainer::class);
        $extendPropertyConfig
            ->expects($this->once())
            ->method('isSchemaUpdateRequired')
            ->with(self::CODE_EXTEND_FIRST, PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(false);

        $this->configManager
            ->expects($this->once())
            ->method('getProviders')
            ->willReturn([$this->configureProvider(self::SCOPE_EXTEND, $extendPropertyConfig)]);

        $extendConfigId = $this->createMock(ConfigIdInterface::class);
        $this->configManager
            ->expects($this->once())
            ->method('getConfigIdByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendConfigId);

        $extendFieldConfig = $this->createMock(ConfigInterface::class);
        $this->configManager
            ->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendFieldConfig);

        $this->configManager
            ->expects($this->once())
            ->method('getConfigChangeSet')
            ->with($extendFieldConfig)
            ->willReturn([self::CODE_EXTEND_FIRST => [1, 2]]);

        $this->assertFalse($this->entityFieldStateChecker->isSchemaUpdateNeeded($fieldConfigModel));
    }

    public function testIsSchemaUpdateNeededWhenNoFormItemsForCode()
    {
        /** @var FieldConfigModel|\PHPUnit_Framework_MockObject_MockObject $fieldConfigModel **/
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);

        /** @var PropertyConfigContainer|\PHPUnit_Framework_MockObject_MockObject $extendPropertyConfig */
        $extendPropertyConfig = $this->createMock(PropertyConfigContainer::class);
        $extendPropertyConfig
            ->expects($this->once())
            ->method('isSchemaUpdateRequired')
            ->with(self::CODE_EXTEND_FIRST, PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(true);

        $extendPropertyConfig
            ->expects($this->once())
            ->method('getFormItems')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn([]);

        $this->configManager
            ->expects($this->once())
            ->method('getProviders')
            ->willReturn([$this->configureProvider(self::SCOPE_EXTEND, $extendPropertyConfig)]);

        $extendConfigId = $this->createMock(ConfigIdInterface::class);
        $this->configManager
            ->expects($this->once())
            ->method('getConfigIdByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendConfigId);

        $extendFieldConfig = $this->createMock(ConfigInterface::class);
        $this->configManager
            ->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendFieldConfig);

        $this->configManager
            ->expects($this->once())
            ->method('getConfigChangeSet')
            ->with($extendFieldConfig)
            ->willReturn([self::CODE_EXTEND_FIRST => [1, 2]]);

        $this->assertFalse($this->entityFieldStateChecker->isSchemaUpdateNeeded($fieldConfigModel));
    }

    public function testIsSchemaUpdateNeededWhenNoUpdateNeededByCallback()
    {
        /** @var FieldConfigModel|\PHPUnit_Framework_MockObject_MockObject $fieldConfigModel **/
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);

        /** @var PropertyConfigContainer|\PHPUnit_Framework_MockObject_MockObject $extendPropertyConfig */
        $extendPropertyConfig = $this->createMock(PropertyConfigContainer::class);
        $extendPropertyConfig
            ->expects($this->once())
            ->method('isSchemaUpdateRequired')
            ->with(self::CODE_EXTEND_FIRST, PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(true);

        $extendPropertyConfig
            ->expects($this->once())
            ->method('getFormItems')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn([self::CODE_EXTEND_FIRST => [
                'form' => [
                    'type' => self::FORM_TYPE_EXTEND,
                    'options' => []
                ]
            ]]);

        $this->configManager
            ->expects($this->once())
            ->method('getProviders')
            ->willReturn([$this->configureProvider(self::SCOPE_EXTEND, $extendPropertyConfig)]);

        $extendConfigId = $this->createMock(ConfigIdInterface::class);
        $this->configManager
            ->expects($this->once())
            ->method('getConfigIdByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendConfigId);

        $extendFieldConfig = $this->createMock(ConfigInterface::class);
        $this->configManager
            ->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendFieldConfig);

        $this->configManager
            ->expects($this->once())
            ->method('getConfigChangeSet')
            ->with($extendFieldConfig)
            ->willReturn([self::CODE_EXTEND_FIRST => [1, 2]]);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig
            ->expects($this->once())
            ->method('getOption')
            ->with('schema_update_required')
            ->willReturn(function () {
                return false;
            });

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with(self::FORM_TYPE_EXTEND, null, ['config_id' => $extendConfigId])
            ->willReturn($form);

        $this->assertFalse($this->entityFieldStateChecker->isSchemaUpdateNeeded($fieldConfigModel));
    }

    public function testIsSchemaUpdate()
    {
        /** @var FieldConfigModel|\PHPUnit_Framework_MockObject_MockObject $fieldConfigModel **/
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);

        /** @var PropertyConfigContainer|\PHPUnit_Framework_MockObject_MockObject $extendPropertyConfig */
        $extendPropertyConfig = $this->createMock(PropertyConfigContainer::class);
        $extendPropertyConfig
            ->expects($this->once())
            ->method('isSchemaUpdateRequired')
            ->with(self::CODE_EXTEND_FIRST, PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(true);

        $extendPropertyConfig
            ->expects($this->once())
            ->method('getFormItems')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn([self::CODE_EXTEND_FIRST => [
                'form' => [
                    'type' => self::FORM_TYPE_EXTEND,
                    'options' => []
                ]
            ]]);

        $this->configManager
            ->expects($this->once())
            ->method('getProviders')
            ->willReturn([$this->configureProvider(self::SCOPE_EXTEND, $extendPropertyConfig)]);

        $extendConfigId = $this->createMock(ConfigIdInterface::class);
        $this->configManager
            ->expects($this->once())
            ->method('getConfigIdByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendConfigId);

        $extendFieldConfig = $this->createMock(ConfigInterface::class);
        $this->configManager
            ->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($fieldConfigModel, self::SCOPE_EXTEND)
            ->willReturn($extendFieldConfig);

        $this->configManager
            ->expects($this->once())
            ->method('getConfigChangeSet')
            ->with($extendFieldConfig)
            ->willReturn([self::CODE_EXTEND_FIRST => [1, 2]]);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig
            ->expects($this->once())
            ->method('getOption')
            ->with('schema_update_required')
            ->willReturn(function () {
                return true;
            });

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with(self::FORM_TYPE_EXTEND, null, ['config_id' => $extendConfigId])
            ->willReturn($form);

        $this->assertTrue($this->entityFieldStateChecker->isSchemaUpdateNeeded($fieldConfigModel));
    }

    /**
     * @param $scope
     * @param PropertyConfigContainer $propertyConfig
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    private function configureProvider($scope, PropertyConfigContainer $propertyConfig)
    {
        $provider = $this->createMock(ConfigProvider::class);
        $provider
            ->expects($this->any())
            ->method('getScope')
            ->willReturn($scope);

        $provider
            ->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfig);

        return $provider;
    }
}
