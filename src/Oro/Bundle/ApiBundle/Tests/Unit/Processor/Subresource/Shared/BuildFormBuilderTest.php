<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\FormHelper;
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

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var BuildFormBuilder */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->processor = new BuildFormBuilder(new FormHelper($this->formFactory, $this->container));

        $this->context->setParentClassName(self::TEST_PARENT_CLASS_NAME);
        $this->context->setAssociationName(self::TEST_ASSOCIATION_NAME);
    }

    public function testProcessWhenFormBuilderAlreadyExists()
    {
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessWhenFormAlreadyExists()
    {
        $form = $this->createMock(FormInterface::class);

        $this->context->setForm($form);
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasFormBuilder());
        $this->assertSame($form, $this->context->getForm());
    }

    public function testProcessWithDefaultOptions()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

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
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE
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
        $formBuilder = $this->createMock(FormBuilderInterface::class);

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
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE
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
        $formBuilder = $this->createMock(FormBuilderInterface::class);

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
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE
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

    public function testProcessForCustomEventSubscriber()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $eventSubscriberServiceId = 'test_event_subscriber';
        $eventSubscriber = $this->createMock(EventSubscriberInterface::class);

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField(self::TEST_ASSOCIATION_NAME);
        $parentConfig->setFormEventSubscribers([$eventSubscriberServiceId]);

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
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE
                ]
            )
            ->willReturn($formBuilder);

        $this->container->expects($this->once())
            ->method('get')
            ->with($eventSubscriberServiceId)
            ->willReturn($eventSubscriber);

        $formBuilder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->identicalTo($eventSubscriber));
        $formBuilder->expects($this->once())
            ->method('add')
            ->with(self::TEST_ASSOCIATION_NAME, null, []);

        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForCustomEventSubscriberInjectedAsService()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $eventSubscriber = $this->createMock(EventSubscriberInterface::class);

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField(self::TEST_ASSOCIATION_NAME);
        $parentConfig->setFormEventSubscribers([$eventSubscriber]);

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
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->identicalTo($eventSubscriber));
        $formBuilder->expects($this->once())
            ->method('add')
            ->with(self::TEST_ASSOCIATION_NAME, null, []);

        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForCustomEventSubscriberAndCustomFormType()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField(self::TEST_ASSOCIATION_NAME);
        $parentConfig->setFormType('test_form');
        $parentConfig->setFormEventSubscribers(['test_event_subscriber']);

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
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE
                ]
            )
            ->willReturn($formBuilder);

        $this->container->expects($this->never())
            ->method('get');

        $formBuilder->expects($this->never())
            ->method('addEventSubscriber');
        $formBuilder->expects($this->once())
            ->method('add')
            ->with(self::TEST_ASSOCIATION_NAME, null, []);

        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }
}
