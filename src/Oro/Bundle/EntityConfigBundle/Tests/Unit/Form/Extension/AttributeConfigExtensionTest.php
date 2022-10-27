<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Extension\AttributeConfigExtension;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class AttributeConfigExtensionTest extends TypeTestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeConfigProvider;

    /** @var SerializedFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $serializedFieldProvider;

    /** @var AttributeTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeTypeRegistry;

    /** @var AttributeConfigExtension */
    private $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);
        $this->serializedFieldProvider = $this->createMock(SerializedFieldProvider::class);
        $this->attributeTypeRegistry = $this->createMock(AttributeTypeRegistry::class);

        $this->extension = new AttributeConfigExtension(
            $this->attributeConfigProvider,
            $this->serializedFieldProvider,
            $this->attributeTypeRegistry
        );
    }

    public function testBuildForm()
    {
        $fieldConfigModel = $this->getFieldConfigModel();

        $this->assertConfigProviderCalled($fieldConfigModel);
        $this->assertAttributeTypeRegistryCalled($fieldConfigModel, false, false, false);

        $this->dispatcher->expects($this->exactly(2))
            ->method('addListener');

        $attributeTypeBuilder = $this->createMock(FormBuilderInterface::class);
        $attributeTypeBuilder->expects($this->any())
            ->method('getName')
            ->willReturn('attribute');
        $attributeTypeBuilder->expects($this->exactly(4))
            ->method('remove')
            ->withConsecutive(
                ['searchable'],
                ['filterable'],
                ['filter_by'],
                ['sortable']
            );

        $this->builder->add($attributeTypeBuilder);

        $this->extension->buildForm($this->builder, ['config_model' => $fieldConfigModel]);
    }

    public function testBuildFormWithAllFields()
    {
        $fieldConfigModel = $this->getFieldConfigModel();

        $this->assertConfigProviderCalled($fieldConfigModel);
        $this->assertAttributeTypeRegistryCalled($fieldConfigModel, true, true, true);

        $this->dispatcher->expects($this->exactly(2))
            ->method('addListener');

        $attributeTypeBuilder = $this->createMock(FormBuilderInterface::class);
        $attributeTypeBuilder->expects($this->any())
            ->method('getName')
            ->willReturn('attribute');
        $attributeTypeBuilder->expects($this->never())
            ->method('remove');

        $this->builder->add($attributeTypeBuilder);

        $this->extension->buildForm($this->builder, ['config_model' => $fieldConfigModel]);
    }

    public function testBuildFormWithoutAttributeType()
    {
        $fieldConfigModel = $this->getFieldConfigModel();

        $this->assertConfigProviderCalled($fieldConfigModel);

        $this->dispatcher->expects($this->exactly(2))
            ->method('addListener');

        $attributeTypeBuilder = $this->createMock(FormBuilderInterface::class);
        $attributeTypeBuilder->expects($this->any())
            ->method('getName')
            ->willReturn('attribute');

        $this->builder->add($attributeTypeBuilder);
        $this->assertTrue($this->builder->has('attribute'));

        $this->extension->buildForm($this->builder, ['config_model' => $fieldConfigModel]);

        $this->assertFalse($this->builder->has('attribute'));
    }

    private function assertConfigProviderCalled(FieldConfigModel $fieldConfigModel)
    {
        $classConfig = $this->createMock(ConfigInterface::class);
        $classConfig->expects($this->once())
            ->method('is')
            ->with('has_attributes')
            ->willReturn(true);

        $fieldConfig = $this->createMock(ConfigInterface::class);
        $fieldConfig->expects($this->once())
            ->method('is')
            ->with('is_attribute')
            ->willReturn(true);

        $this->attributeConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [$fieldConfigModel->getEntity()->getClassName(), null, $classConfig],
                [$fieldConfigModel->getEntity()->getClassName(), $fieldConfigModel->getFieldName(), $fieldConfig]
            ]);
    }

    private function assertAttributeTypeRegistryCalled(
        FieldConfigModel $attribute,
        bool $isSearchable,
        bool $isFilterable,
        bool $isSortable
    ) {
        $attributeType = $this->createMock(AttributeTypeInterface::class);
        $attributeType->expects($this->once())
            ->method('isSearchable')
            ->willReturn($isSearchable);
        $attributeType->expects($this->once())
            ->method('isFilterable')
            ->willReturn($isFilterable);
        $attributeType->expects($this->once())
            ->method('isSortable')
            ->willReturn($isSortable);

        $this->attributeTypeRegistry->expects($this->once())
            ->method('getAttributeType')
            ->with($attribute)
            ->willReturn($attributeType);
    }

    public function testBuildFormNotConfigurable()
    {
        $fieldConfigModel = $this->getFieldConfigModel();

        $classConfig = $this->createMock(ConfigInterface::class);
        $classConfig->expects($this->once())
            ->method('is')
            ->with('has_attributes')
            ->willReturn(false);
        $fieldConfig = $this->createMock(ConfigInterface::class);
        $fieldConfig->expects($this->once())
            ->method('is')
            ->with('is_attribute')
            ->willReturn(false);
        $this->attributeConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->withConsecutive(
                [$fieldConfigModel->getEntity()->getClassName()],
                [$fieldConfigModel->getEntity()->getClassName(), $fieldConfigModel->getFieldName()]
            )
            ->willReturnOnConsecutiveCalls($classConfig, $fieldConfig);

        $this->builder->add('attribute');
        $this->dispatcher->expects($this->never())
            ->method('addListener');

        $this->extension->buildForm($this->builder, ['config_model' => $fieldConfigModel]);
        $this->assertFalse($this->builder->has('attribute'));
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([ConfigType::class], AttributeConfigExtension::getExtendedTypes());
    }

    public function testOnPostSetData()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('remove')
            ->with('is_serialized');

        $event = new FormEvent($form, []);
        $this->extension->onPostSetData($event);
    }

    public function isSerializedDataProvider(): array
    {
        return [
            'serialized field' => [
                'is_serialized' => true
            ],
            'not serialized field' => [
                'is_serialized' => false
            ],
        ];
    }

    /**
     * @dataProvider isSerializedDataProvider
     */
    public function testOnPostSubmit(bool $isSerialized)
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('config_model')
            ->willReturn($this->getFieldConfigModel());

        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $data = [];
        $event = new FormEvent($form, $data);
        $fieldConfigModel = $this->getFieldConfigModel();
        $this->serializedFieldProvider->expects($this->once())
            ->method('isSerializedByData')
            ->with($fieldConfigModel, $data)
            ->willReturn($isSerialized);
        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->any())
            ->method('is')
            ->willReturn(true);
        $this->attributeConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
        $this->dispatcher->expects($this->any())
            ->method('addListener');
        $this->extension->buildForm($this->builder, ['config_model' => $fieldConfigModel]);

        $this->extension->onPostSubmit($event);

        $expectedData = [
            'extend'=> [
                'is_serialized' => $isSerialized
            ]
        ];

        $this->assertEquals($expectedData, $event->getData());
    }

    public function testOnPostSubmitNotValid()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $this->serializedFieldProvider->expects($this->never())
            ->method('isSerializedByData');

        $event = new FormEvent($form, []);
        $this->extension->onPostSubmit($event);
    }

    public function testOnPostIsValidAndFieldConfigModelExists(): void
    {
        $fieldConfigModel = $this->getFieldConfigModel(1);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('config_model')
            ->willReturn($fieldConfigModel);

        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $this->serializedFieldProvider->expects($this->never())
            ->method('isSerializedByData');

        $event = new FormEvent($form, []);
        $this->extension->buildForm($this->builder, ['config_model' => $fieldConfigModel]);
        $this->extension->onPostSubmit($event);

        $this->assertEmpty($event->getData());
    }

    private function getFieldConfigModel(int $id = null): FieldConfigModel
    {
        $entityConfigModel = new EntityConfigModel('class');
        $fieldConfigModel = new FieldConfigModel('test', 'string');
        ReflectionUtil::setId($fieldConfigModel, $id);
        $fieldConfigModel->setEntity($entityConfigModel);

        return $fieldConfigModel;
    }
}
