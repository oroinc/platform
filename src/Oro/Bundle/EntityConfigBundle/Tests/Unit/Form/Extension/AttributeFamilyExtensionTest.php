<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Form\Extension\AttributeFamilyExtension;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraints\NotBlank;

class AttributeFamilyExtensionTest extends TypeTestCase
{
    const DATA_CLASS = TestActivityTarget::class;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $attributeConfigProvider;

    /**
     * @var AttributeFamilyExtension
     */
    protected $extension;

    protected function setUp()
    {
        parent::setUp();

        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);

        $this->extension = new AttributeFamilyExtension($this->attributeConfigProvider);
    }

    /**
     * @return array
     */
    public function notApplicableDataProvider()
    {
        return [
            'no data_class option' => [
                'options' => []
            ],
            'wrong data_class option' => [
                'options' => ['data_class' => \stdClass::class]
            ],
            'disabled extension' => [
                'options' => ['enable_attribute_family' => false]
            ]
        ];
    }

    /**
     * @dataProvider notApplicableDataProvider
     *
     * @param array $options
     */
    public function testBuildFormWhenNotApplicable(array $options)
    {
        $this->attributeConfigProvider->expects($this->never())
            ->method('hasConfig');
        $this->attributeConfigProvider->expects($this->never())
            ->method('getConfig');
        $this->dispatcher->expects($this->never())
            ->method('addListener');

        $this->extension->buildForm($this->builder, $options);
    }

    public function testBuildFormWithoutConfig()
    {
        $this->attributeConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::DATA_CLASS)
            ->willReturn(false);
        $this->attributeConfigProvider->expects($this->never())
            ->method('getConfig');
        $this->dispatcher->expects($this->never())
            ->method('addListener');

        $this->extension->buildForm(
            $this->builder,
            ['data_class' => self::DATA_CLASS, 'enable_attribute_family' => true]
        );
    }

    public function testBuildFormHasNotAttributes()
    {
        $this->attributeConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::DATA_CLASS)
            ->willReturn(true);

        $attributeConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $attributeConfig->expects($this->once())
            ->method('is')
            ->with('has_attributes')
            ->willReturn(false);
        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::DATA_CLASS)
            ->willReturn($attributeConfig);
        $this->dispatcher->expects($this->never())
            ->method('addListener');

        $this->extension->buildForm(
            $this->builder,
            ['data_class' => self::DATA_CLASS, 'enable_attribute_family' => true]
        );
    }

    public function testBuildForm()
    {
        $this->attributeConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::DATA_CLASS)
            ->willReturn(true);

        $attributeConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $attributeConfig->expects($this->once())
            ->method('is')
            ->with('has_attributes')
            ->willReturn(true);
        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::DATA_CLASS)
            ->willReturn($attributeConfig);
        $this->dispatcher->expects($this->once())
            ->method('addListener')
            ->with(FormEvents::PRE_SET_DATA, [$this->extension, 'onPreSetData'], 0);

        $this->extension->buildForm(
            $this->builder,
            ['data_class' => self::DATA_CLASS, 'enable_attribute_family' => true]
        );
    }

    /**
     * @return array
     */
    public function preSetDataProvider()
    {
        return [
            'null entity' => [
               'entity' => null,
            ],
            'no family entity' => [
                'entity' => new TestActivityTarget()
            ]
        ];
    }

    /**
     * @dataProvider preSetDataProvider
     * @param null|TestActivityTarget $entity
     */
    public function testOnPreSetData($entity)
    {
        $formConfig = $this->getMockBuilder(FormConfigInterface::class)
            ->getMock();
        $formConfig->expects($this->once())
            ->method('getOptions')
            ->willReturn(['data_class' => self::DATA_CLASS]);
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->getMockBuilder(FormInterface::class)
            ->getMock();
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);
        $form->expects($this->once())
            ->method('add')
            ->with(
                'attributeFamily',
                EntityType::class,
                [
                    'class' => AttributeFamily::class,
                    'label' => 'oro.entity_config.attribute_family.entity_label',
                    'query_builder' => function () {
                    },
                    'required' => true,
                    'constraints' => [new NotBlank()]
                ]
            );

        $this->extension->onPreSetData(new FormEvent($form, $entity));
    }
}
