<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Extension\AttributeFieldConfigExtension;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class AttributeFieldConfigExtensionTest extends TypeTestCase
{
    /**
     * @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeConfigProvider;

    /**
     * @var AttributeFieldConfigExtension
     */
    protected $extension;

    protected function setUp()
    {
        parent::setUp();

        $this->attributeConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new AttributeFieldConfigExtension($this->attributeConfigProvider);
    }

    public function testBuildForm()
    {
        $this->dispatcher->expects($this->once())
            ->method('addListener')
            ->with(FormEvents::POST_SET_DATA, [$this->extension, 'onPostSetData'], 0);

        $this->extension->buildForm($this->builder, []);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals('oro_entity_extend_field_type', $this->extension->getExtendedType());
    }

    public function testOnPostSetData()
    {
        $fieldConfigModel = $this->getFieldConfigModel();
        $config = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $config->expects($this->once())
            ->method('is')
            ->with('has_attributes')
            ->willReturn(true);
        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($fieldConfigModel->getEntity()->getClassName())
            ->willReturn($config);

        $form = $this->getMockBuilder(FormInterface::class)
            ->getMock();
        $form->expects($this->once())
            ->method('remove')
            ->with('is_serialized');
        $event = new FormEvent($form, $fieldConfigModel);
        $this->extension->onPostSetData($event);
    }

    /**
     * @return FieldConfigModel
     */
    protected function getFieldConfigModel()
    {
        $entityConfigModel = new EntityConfigModel('class');
        $fieldConfigModel = new FieldConfigModel('test', 'string');
        $fieldConfigModel->setEntity($entityConfigModel);
        $fieldConfigModel->fromArray('attribute', ['is_attribute' => true], []);

        return $fieldConfigModel;
    }
}
