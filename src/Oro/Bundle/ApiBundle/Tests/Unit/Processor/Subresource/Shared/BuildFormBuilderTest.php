<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\BuildFormBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;

class BuildFormBuilderTest extends ChangeRelationshipProcessorTestCase
{
    const TEST_PARENT_CLASS_NAME = 'Test\Entity';
    const TEST_ASSOCIATION_NAME  = 'testAssociation';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var BuildFormBuilder */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->processor = new BuildFormBuilder($this->formFactory);

        $this->context->setParentClassName(self::TEST_PARENT_CLASS_NAME);
        $this->context->setAssociationName(self::TEST_ASSOCIATION_NAME);
    }

    public function testProcessWhenFormBuilderAlreadyExists()
    {
        $formBuilder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessWhenFormAlreadyExists()
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->context->setForm($form);
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasFormBuilder());
        $this->assertSame($form, $this->context->getForm());
    }

    public function testProcessWithDefaultOptions()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField(self::TEST_ASSOCIATION_NAME);

        $this->formFactory->expects($this->once())
            ->method('createNamedBuilder')
            ->with(
                null,
                'form',
                $parentEntity,
                [
                    'data_class'           => self::TEST_PARENT_CLASS_NAME,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects($this->once())
            ->method('add')
            ->with(
                self::TEST_ASSOCIATION_NAME,
                null,
                []
            );

        $this->context->setParentConfig($parentConfig);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessWithCustomOptions()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');

        $parentConfig = new EntityDefinitionConfig();
        $associationConfig = $parentConfig->addField(self::TEST_ASSOCIATION_NAME);
        $associationConfig->setPropertyPath('realAssociationName');
        $associationConfig->setFormType('customType');
        $associationConfig->setFormOptions(['trim' => false]);

        $this->formFactory->expects($this->once())
            ->method('createNamedBuilder')
            ->with(
                null,
                'form',
                $parentEntity,
                [
                    'data_class'           => self::TEST_PARENT_CLASS_NAME,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects($this->once())
            ->method('add')
            ->with(
                self::TEST_ASSOCIATION_NAME,
                'customType',
                ['property_path' => 'realAssociationName', 'trim' => false]
            );

        $this->context->setParentConfig($parentConfig);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }
}
