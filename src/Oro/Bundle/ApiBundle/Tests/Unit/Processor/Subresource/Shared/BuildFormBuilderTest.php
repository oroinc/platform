<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\BuildFormBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

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

        $this->formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');

        $this->processor = new BuildFormBuilder($this->formFactory);

        $this->context->setParentClassName(self::TEST_PARENT_CLASS_NAME);
        $this->context->setAssociationName(self::TEST_ASSOCIATION_NAME);
    }

    public function testProcessWhenFormBuilderAlreadyExists()
    {
        $formBuilder = $this->createMock('Symfony\Component\Form\FormBuilderInterface');

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessWhenFormAlreadyExists()
    {
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $this->context->setForm($form);
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasFormBuilder());
        $this->assertSame($form, $this->context->getForm());
    }

    public function testProcessWithDefaultOptions()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->createMock('Symfony\Component\Form\FormBuilderInterface');

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField(self::TEST_ASSOCIATION_NAME);

        $parentMetadata = new EntityMetadata();
        $parentMetadata->addAssociation(new AssociationMetadata(self::TEST_ASSOCIATION_NAME));

        $this->formFactory->expects($this->once())
            ->method('createNamedBuilder')
            ->with(
                null,
                'form',
                $parentEntity,
                [
                    'data_class'           => self::TEST_PARENT_CLASS_NAME,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => FormUtil::EXTRA_FIELDS_MESSAGE
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
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessWithCustomOptions()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->createMock('Symfony\Component\Form\FormBuilderInterface');

        $parentConfig = new EntityDefinitionConfig();
        $associationConfig = $parentConfig->addField(self::TEST_ASSOCIATION_NAME);
        $associationConfig->setPropertyPath('realAssociationName');
        $associationConfig->setFormType('customType');
        $associationConfig->setFormOptions(['trim' => false]);

        $parentMetadata = new EntityMetadata();
        $parentMetadata->addAssociation(new AssociationMetadata(self::TEST_ASSOCIATION_NAME))
            ->setPropertyPath('realAssociationName');

        $this->formFactory->expects($this->once())
            ->method('createNamedBuilder')
            ->with(
                null,
                'form',
                $parentEntity,
                [
                    'data_class'           => self::TEST_PARENT_CLASS_NAME,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => FormUtil::EXTRA_FIELDS_MESSAGE
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
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessWhenAssociationShouldNotBeMapped()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->createMock('Symfony\Component\Form\FormBuilderInterface');

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField(self::TEST_ASSOCIATION_NAME);

        $parentMetadata = new EntityMetadata();
        $parentMetadata->addAssociation(new AssociationMetadata(self::TEST_ASSOCIATION_NAME))
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $this->formFactory->expects($this->once())
            ->method('createNamedBuilder')
            ->with(
                null,
                'form',
                $parentEntity,
                [
                    'data_class'           => self::TEST_PARENT_CLASS_NAME,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => FormUtil::EXTRA_FIELDS_MESSAGE
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects($this->once())
            ->method('add')
            ->with(
                self::TEST_ASSOCIATION_NAME,
                null,
                ['mapped' => false]
            );

        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }
}
