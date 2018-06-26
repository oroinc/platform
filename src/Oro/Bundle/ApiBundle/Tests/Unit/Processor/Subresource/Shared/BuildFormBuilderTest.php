<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\BuildFormBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class BuildFormBuilderTest extends ChangeRelationshipProcessorTestCase
{
    private const TEST_PARENT_CLASS_NAME = 'Test\Entity';
    private const TEST_ASSOCIATION_NAME  = 'testAssociation';

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface */
    private $formFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    /** @var BuildFormBuilder */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->processor = new BuildFormBuilder(
            new FormHelper(
                $this->formFactory,
                $this->createMock(PropertyAccessorInterface::class),
                $this->container
            )
        );

        $this->context->setParentClassName(self::TEST_PARENT_CLASS_NAME);
        $this->context->setAssociationName(self::TEST_ASSOCIATION_NAME);
    }

    public function testProcessWhenFormBuilderAlreadyExists()
    {
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessWhenFormAlreadyExists()
    {
        $form = $this->createMock(FormInterface::class);

        $this->context->setForm($form);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasFormBuilder());
        self::assertSame($form, $this->context->getForm());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The parent entity object must be added to the context before creation of the form builder.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessWhenNoParentEntity()
    {
        $this->formFactory->expects(self::never())
            ->method('createNamedBuilder');

        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->context->setParentMetadata(new EntityMetadata());
        $this->processor->process($this->context);
    }

    public function testProcessWithDefaultOptions()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField(self::TEST_ASSOCIATION_NAME);

        $parentMetadata = new EntityMetadata();
        $parentMetadata->addAssociation(new AssociationMetadata(self::TEST_ASSOCIATION_NAME));

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $parentEntity,
                [
                    'data_class'           => self::TEST_PARENT_CLASS_NAME,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'    => false
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
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
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForParentApiResourceBasedOnManageableEntity()
    {
        $parentEntityClass = UserProfile::class;
        $parentBaseEntityClass = User::class;
        $parentEntity = new User();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setParentResourceClass($parentBaseEntityClass);
        $parentConfig->addField(self::TEST_ASSOCIATION_NAME);

        $parentMetadata = new EntityMetadata();
        $parentMetadata->addAssociation(new AssociationMetadata(self::TEST_ASSOCIATION_NAME));

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $parentEntity,
                [
                    'data_class'           => $parentEntityClass,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'    => false
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('add')
            ->with(
                self::TEST_ASSOCIATION_NAME,
                null,
                []
            );

        $this->context->setParentClassName($parentEntityClass);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForParentApiResourceBasedOnManageableEntityWithCustomProcessorToLoadParentEntity()
    {
        $parentEntityClass = UserProfile::class;
        $parentBaseEntityClass = User::class;
        $parentEntity = new UserProfile();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setParentResourceClass($parentBaseEntityClass);
        $parentConfig->addField(self::TEST_ASSOCIATION_NAME);

        $parentMetadata = new EntityMetadata();
        $parentMetadata->addAssociation(new AssociationMetadata(self::TEST_ASSOCIATION_NAME));

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $parentEntity,
                [
                    'data_class'           => $parentEntityClass,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'    => false
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('add')
            ->with(
                self::TEST_ASSOCIATION_NAME,
                null,
                []
            );

        $this->context->setParentClassName($parentEntityClass);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessWithCustomOptions()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setFormOptions(['validation_groups' => ['test', 'api']]);
        $associationConfig = $parentConfig->addField(self::TEST_ASSOCIATION_NAME);
        $associationConfig->setPropertyPath('realAssociationName');
        $associationConfig->setFormType('customType');
        $associationConfig->setFormOptions(['trim' => false]);

        $parentMetadata = new EntityMetadata();
        $parentMetadata->addAssociation(new AssociationMetadata(self::TEST_ASSOCIATION_NAME))
            ->setPropertyPath('realAssociationName');

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $parentEntity,
                [
                    'data_class'           => self::TEST_PARENT_CLASS_NAME,
                    'validation_groups'    => ['test', 'api'],
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'    => false
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
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
        self::assertSame($formBuilder, $this->context->getFormBuilder());
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

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $parentEntity,
                [
                    'data_class'           => self::TEST_PARENT_CLASS_NAME,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'    => false
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
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
        self::assertSame($formBuilder, $this->context->getFormBuilder());
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

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $parentEntity,
                [
                    'data_class'           => self::TEST_PARENT_CLASS_NAME,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'    => false
                ]
            )
            ->willReturn($formBuilder);

        $this->container->expects(self::once())
            ->method('get')
            ->with($eventSubscriberServiceId)
            ->willReturn($eventSubscriber);

        $formBuilder->expects(self::once())
            ->method('addEventSubscriber')
            ->with(self::identicalTo($eventSubscriber));
        $formBuilder->expects(self::once())
            ->method('add')
            ->with(self::TEST_ASSOCIATION_NAME, null, []);

        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
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

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $parentEntity,
                [
                    'data_class'           => self::TEST_PARENT_CLASS_NAME,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'    => false
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('addEventSubscriber')
            ->with(self::identicalTo($eventSubscriber));
        $formBuilder->expects(self::once())
            ->method('add')
            ->with(self::TEST_ASSOCIATION_NAME, null, []);

        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
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

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $parentEntity,
                [
                    'data_class'           => self::TEST_PARENT_CLASS_NAME,
                    'validation_groups'    => ['Default', 'api'],
                    'extra_fields_message' => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'    => false
                ]
            )
            ->willReturn($formBuilder);

        $this->container->expects(self::never())
            ->method('get');

        $formBuilder->expects(self::never())
            ->method('addEventSubscriber');
        $formBuilder->expects(self::once())
            ->method('add')
            ->with(self::TEST_ASSOCIATION_NAME, null, []);

        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }
}
