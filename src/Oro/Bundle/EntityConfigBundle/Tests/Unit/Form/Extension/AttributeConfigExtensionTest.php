<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Extension\AttributeConfigExtension;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;

use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class AttributeConfigExtensionTest extends TypeTestCase
{
    /**
     * @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeConfigProvider;

    /**
     * @var SerializedFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $serializedFieldProvider;

    /**
     * @var AttributeConfigExtension
     */
    protected $extension;

    protected function setUp()
    {
        parent::setUp();

        $this->attributeConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializedFieldProvider = $this->getMockBuilder(SerializedFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new AttributeConfigExtension(
            $this->attributeConfigProvider,
            $this->serializedFieldProvider
        );
    }

    public function testBuildForm()
    {
        $fieldConfigModel = $this->getFieldConfigModel();

        $classConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $classConfig->expects($this->once())
            ->method('is')
            ->with('has_attributes')
            ->willReturn(true);
        $fieldConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $fieldConfig->expects($this->once())
            ->method('is')
            ->with('is_attribute')
            ->willReturn(true);
        $this->attributeConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->withConsecutive(
                [$fieldConfigModel->getEntity()->getClassName()],
                [$fieldConfigModel->getEntity()->getClassName(), $fieldConfigModel->getFieldName()]
            )
            ->willReturnOnConsecutiveCalls($classConfig, $fieldConfig);

        $this->dispatcher->expects($this->exactly(2))
            ->method('addListener');

        $this->extension->buildForm($this->builder, ['config_model' => $fieldConfigModel]);
    }

    public function testBuildFormNotConfigurable()
    {
        $fieldConfigModel = $this->getFieldConfigModel();

        $classConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $classConfig->expects($this->once())
            ->method('is')
            ->with('has_attributes')
            ->willReturn(false);
        $fieldConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
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

    public function testGetExtendedType()
    {
        $this->assertEquals('oro_entity_config_type', $this->extension->getExtendedType());
    }

    public function testOnPostSetData()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMockBuilder(FormInterface::class)
            ->getMock();
        $form->expects($this->once())
            ->method('remove')
            ->with('is_serialized');

        $event = new FormEvent($form, []);
        $this->extension->onPostSetData($event);
    }

    /**
     * @return array
     */
    public function isSerializedDataProvider()
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
     *
     * @param bool $isSerialized
     */
    public function testOnPostSubmit($isSerialized)
    {
        $form = $this->getMockBuilder(FormInterface::class)
            ->getMock();
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
        $config = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
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
        $form = $this->getMockBuilder(FormInterface::class)
            ->getMock();
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $this->serializedFieldProvider->expects($this->never())
            ->method('isSerializedByData');

        $event = new FormEvent($form, []);
        $this->extension->onPostSubmit($event);
    }

    /**
     * @return FieldConfigModel
     */
    protected function getFieldConfigModel()
    {
        $entityConfigModel = new EntityConfigModel('class');
        $fieldConfigModel = new FieldConfigModel('test', 'string');
        $fieldConfigModel->setEntity($entityConfigModel);

        return $fieldConfigModel;
    }
}
