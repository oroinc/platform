<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Form\Guesser\DataTypeGuesser;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildFormBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BuildFormBuilderTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface */
    private $formFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    /** @var BuildFormBuilder */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->processor = new BuildFormBuilder(
            new FormHelper(
                $this->formFactory,
                $this->createMock(DataTypeGuesser::class),
                $this->createMock(PropertyAccessorInterface::class),
                $this->container
            ),
            $this->doctrineHelper
        );
    }

    /**
     * @param string $fieldName
     *
     * @return MetaPropertyMetadata
     */
    private function createMetaPropertyMetadata($fieldName)
    {
        $fieldMetadata = new MetaPropertyMetadata();
        $fieldMetadata->setName($fieldName);

        return $fieldMetadata;
    }

    /**
     * @param string $fieldName
     *
     * @return FieldMetadata
     */
    private function createFieldMetadata($fieldName)
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);

        return $fieldMetadata;
    }

    /**
     * @param string $associationName
     *
     * @return AssociationMetadata
     */
    private function createAssociationMetadata($associationName)
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);

        return $associationMetadata;
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

    public function testProcessWhenNoEntity()
    {
        $this->expectException(\Oro\Bundle\ApiBundle\Exception\RuntimeException::class);
        $this->expectExceptionMessage(
            'The entity object must be added to the context before creation of the form builder.'
        );

        $this->formFactory->expects(self::never())
            ->method('createNamedBuilder');

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setMetadata(new EntityMetadata());
        $this->processor->process($this->context);
    }

    public function testProcessForCustomForm()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formType = 'test_form';
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $config->setFormType($formType);
        $config->setFormOptions(['validation_groups' => ['Default', 'api', 'my_group']]);

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                $formType,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api', 'my_group'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);
        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::never())
            ->method('add');

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcess()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $config->addField('metaProperty1');
        $config->addField('metaProperty2')->setPropertyPath('realMetaProperty2');
        $config->addField('field1');
        $config->addField('field2')->setPropertyPath('realField2');
        $configField3 = $config->addField('field3');
        $configField3->setPropertyPath('realField3');
        $configField3->setFormType('text');
        $configField3->setFormOptions(['trim' => false]);
        $config->addField('association1');
        $config->addField('association2')->setPropertyPath('realAssociation2');
        $configAssociation3 = $config->addField('association3');
        $configAssociation3->setPropertyPath('realAssociation3');
        $configAssociation3->setFormType('text');
        $configAssociation3->setFormOptions(['trim' => false]);

        $metadata = new EntityMetadata();
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('metaProperty1'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('metaProperty2'))
            ->setPropertyPath('realMetaProperty2');
        $metadata->addField($this->createFieldMetadata('field1'));
        $metadata->addField($this->createFieldMetadata('field2'))
            ->setPropertyPath('realField2');
        $metadata->addField($this->createFieldMetadata('field3'))
            ->setPropertyPath('realField3');
        $metadata->addAssociation($this->createAssociationMetadata('association1'));
        $metadata->addAssociation($this->createAssociationMetadata('association2'))
            ->setPropertyPath('realAssociation2');
        $metadata->addAssociation($this->createAssociationMetadata('association3'))
            ->setPropertyPath('realAssociation3');

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::exactly(8))
            ->method('add')
            ->withConsecutive(
                ['metaProperty1', null, []],
                ['metaProperty2', null, ['property_path' => 'realMetaProperty2']],
                ['field1', null, []],
                ['field2', null, ['property_path' => 'realField2']],
                ['field3', 'text', ['property_path' => 'realField3', 'trim' => false]],
                ['association1', null, []],
                ['association2', null, ['property_path' => 'realAssociation2']],
                ['association3', 'text', ['property_path' => 'realAssociation3', 'trim' => false]]
            );

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForClassNameMetaProperty()
    {
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $config->addField(ConfigUtil::CLASS_NAME);

        $metadata = new EntityMetadata();
        $metadata->addMetaProperty($this->createMetaPropertyMetadata(ConfigUtil::CLASS_NAME));

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::never())
            ->method('add');

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForRenamedClassNameMetaProperty()
    {
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $config->addField('renamedClassName')->setPropertyPath(ConfigUtil::CLASS_NAME);

        $metadata = new EntityMetadata();
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('renamedClassName'))
            ->setPropertyPath(ConfigUtil::CLASS_NAME);

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::never())
            ->method('add');

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForApiResourceBasedOnManageableEntity()
    {
        $entityClass = UserProfile::class;
        $parentEntityClass = User::class;
        $data = new User();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentEntityClass);
        $config->addField('field1');

        $metadata = new EntityMetadata();
        $metadata->addField($this->createFieldMetadata('field1'));

        $this->doctrineHelper->expects(self::once())
            ->method('getClass')
            ->with(self::identicalTo($data))
            ->willReturn($parentEntityClass);

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $parentEntityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::once())
            ->method('add')
            ->with('field1', null, []);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForApiResourceBasedOnManageableEntityWithCustomProcessorToLoadEntity()
    {
        $entityClass = UserProfile::class;
        $parentEntityClass = User::class;
        $data = new UserProfile();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentEntityClass);
        $config->addField('field1');

        $metadata = new EntityMetadata();
        $metadata->addField($this->createFieldMetadata('field1'));

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::once())
            ->method('add')
            ->with('field1', null, []);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForIgnoredField()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $config->addField('field1')
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $metadata = new EntityMetadata();
        $metadata->addField($this->createFieldMetadata('field1'))
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::once())
            ->method('add')
            ->with('field1', null, ['mapped' => false]);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForFieldIgnoredOnlyForGetActions()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $config->addField('field1')
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $metadata = new EntityMetadata();
        $metadata->addField($this->createFieldMetadata('field1'));

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::once())
            ->method('add')
            ->with('field1', null, []);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForIgnoredAssociation()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $config->addField('association1')
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $metadata = new EntityMetadata();
        $metadata->addAssociation($this->createAssociationMetadata('association1'))
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::once())
            ->method('add')
            ->with('association1', null, ['mapped' => false]);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForAssociationIgnoredOnlyForGetActions()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $config->addField('association1')
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $metadata = new EntityMetadata();
        $metadata->addAssociation($this->createAssociationMetadata('association1'));

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::once())
            ->method('add')
            ->with('association1', null, []);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForCustomEventSubscriber()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $eventSubscriberServiceId = 'test_event_subscriber';
        $eventSubscriber = $this->createMock(EventSubscriberInterface::class);

        $config = new EntityDefinitionConfig();
        $config->setFormEventSubscribers([$eventSubscriberServiceId]);

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
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

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata(new EntityMetadata());
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForCustomEventSubscriberInjectedAsService()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $eventSubscriber = $this->createMock(EventSubscriberInterface::class);

        $config = new EntityDefinitionConfig();
        $config->setFormEventSubscribers([$eventSubscriber]);

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('addEventSubscriber')
            ->with(self::identicalTo($eventSubscriber));

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata(new EntityMetadata());
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForOutputOnlyField()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $config->addField('field1')
            ->setDirection('output-only');

        $metadata = new EntityMetadata();
        $metadata->addField($this->createFieldMetadata('field1'))
            ->setDirection(false, true);

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::never())
            ->method('add');

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForOutputOnlyAssociation()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $config->addField('association1')
            ->setDirection('output-only');

        $metadata = new EntityMetadata();
        $metadata->addAssociation($this->createAssociationMetadata('association1'))
            ->setDirection(false, true);

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::never())
            ->method('add');

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForEnabledFullValidation()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => true,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $this->context->setClassName($entityClass);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setMetadata(new EntityMetadata());
        $this->context->setResult($data);
        $this->processor = new BuildFormBuilder(
            new FormHelper(
                $this->formFactory,
                $this->createMock(DataTypeGuesser::class),
                $this->createMock(PropertyAccessorInterface::class),
                $this->container
            ),
            $this->doctrineHelper,
            true
        );
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForDisabledFullValidation()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::never())
            ->method('addEventSubscriber');

        $this->context->setClassName($entityClass);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setMetadata(new EntityMetadata());
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForAssociationThatRepresentedAsField()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $configAssociation = $config->addField('association');
        $configAssociation->setDataType('object');
        $configAssociation->setFormOptions(['trim' => false]);

        $metadata = new EntityMetadata();
        $metadata->addAssociation($this->createAssociationMetadata('association'));

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::once())
            ->method('add')
            ->withConsecutive(
                ['association', null, []]
            );

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForAssociationThatRepresentedAsFieldWithCustomFormType()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $configAssociation = $config->addField('association');
        $configAssociation->setDataType('object');
        $configAssociation->setFormType('text');
        $configAssociation->setFormOptions(['trim' => false]);

        $metadata = new EntityMetadata();
        $metadata->addAssociation($this->createAssociationMetadata('association'));

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::once())
            ->method('add')
            ->withConsecutive(
                ['association', 'text', ['trim' => false]]
            );

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }

    public function testProcessForAssociationThatRepresentedAsFieldAndMappedOptionIsFalse()
    {
        $entityClass = 'Test\Entity';
        $data = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $config = new EntityDefinitionConfig();
        $configAssociation = $config->addField('association');
        $configAssociation->setDataType('object');
        $configAssociation->setFormOptions(['trim' => false, 'mapped' => false]);

        $metadata = new EntityMetadata();
        $metadata->addAssociation($this->createAssociationMetadata('association'));

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->with(
                null,
                FormType::class,
                $data,
                [
                    'data_class'             => $entityClass,
                    'validation_groups'      => ['Default', 'api'],
                    'extra_fields_message'   => FormHelper::EXTRA_FIELDS_MESSAGE,
                    'enable_validation'      => false,
                    'enable_full_validation' => false,
                    'api_context'            => $this->context
                ]
            )
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('setDataMapper')
            ->with(self::isInstanceOf(PropertyPathMapper::class));
        $formBuilder->expects(self::once())
            ->method('add')
            ->withConsecutive(
                ['association', null, ['trim' => false, 'mapped' => false]]
            );

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }
}
