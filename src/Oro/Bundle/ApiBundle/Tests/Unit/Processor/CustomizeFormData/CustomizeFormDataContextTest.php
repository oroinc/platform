<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CustomizeFormDataContextTest extends \PHPUnit\Framework\TestCase
{
    private CustomizeFormDataContext $context;

    protected function setUp(): void
    {
        $this->context = new CustomizeFormDataContext();
    }

    private function getFormConfig(
        string $name,
        bool $compound = false,
        string $propertyPath = null
    ): FormConfigInterface {
        $config = $this->createMock(FormConfigInterface::class);
        $config->expects(self::any())
            ->method('getName')
            ->willReturn($name);
        $config->expects(self::any())
            ->method('getCompound')
            ->willReturn($compound);
        $config->expects(self::any())
            ->method('getDataMapper')
            ->willReturn($compound ? $this->createMock(DataMapperInterface::class) : null);
        $config->expects(self::any())
            ->method('getInheritData')
            ->willReturn(false);
        $config->expects(self::any())
            ->method('getPropertyPath')
            ->willReturn(new PropertyPath($propertyPath ?? $name));

        return $config;
    }

    public function testIsInitialized()
    {
        self::assertFalse($this->context->isInitialized());

        $this->context->setForm($this->createMock(FormInterface::class));
        self::assertTrue($this->context->isInitialized());
    }

    public function testRootClassName()
    {
        self::assertNull($this->context->getRootClassName());

        $className = 'Test\Class';
        $this->context->setRootClassName($className);
        self::assertEquals($className, $this->context->getRootClassName());
    }

    public function testClassName()
    {
        $className = 'Test\Class';
        $this->context->setClassName($className);
        self::assertEquals($className, $this->context->getClassName());
    }

    public function testPropertyPath()
    {
        self::assertNull($this->context->getPropertyPath());

        $propertyPath = 'field1.field11';
        $this->context->setPropertyPath($propertyPath);
        self::assertEquals($propertyPath, $this->context->getPropertyPath());
    }

    public function testRootConfig()
    {
        self::assertNull($this->context->getRootConfig());

        $config = new EntityDefinitionConfig();
        $this->context->setRootConfig($config);
        self::assertSame($config, $this->context->getRootConfig());

        $this->context->setRootConfig(null);
        self::assertNull($this->context->getRootConfig());
    }

    public function testConfig()
    {
        self::assertNull($this->context->getConfig());

        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertSame($config, $this->context->getConfig());

        $this->context->setConfig(null);
        self::assertNull($this->context->getConfig());
    }

    public function testSharedData()
    {
        $sharedData = $this->createMock(ParameterBagInterface::class);

        $this->context->setSharedData($sharedData);
        self::assertSame($sharedData, $this->context->getSharedData());
    }

    public function testGetNormalizationContext()
    {
        $action = 'test_action';
        $version = '1.2';
        $sharedData = $this->createMock(ParameterBagInterface::class);
        $this->context->setAction($action);
        $this->context->setVersion($version);
        $this->context->setSharedData($sharedData);
        $this->context->getRequestType()->add('test_request_type');
        $requestType = $this->context->getRequestType();

        $normalizationContext = $this->context->getNormalizationContext();
        self::assertCount(4, $normalizationContext);
        self::assertSame($action, $normalizationContext['action']);
        self::assertSame($version, $normalizationContext['version']);
        self::assertSame($requestType, $normalizationContext['requestType']);
        self::assertSame($sharedData, $normalizationContext['sharedData']);
    }

    public function testIncludedEntities()
    {
        self::assertNull($this->context->getIncludedEntities());

        $includedEntities = $this->createMock(IncludedEntityCollection::class);
        $this->context->setIncludedEntities($includedEntities);
        self::assertSame($includedEntities, $this->context->getIncludedEntities());
    }

    public function testIsPrimaryEntityRequestForPrimaryEntityRequest()
    {
        $primaryEntity = new \stdClass();
        $includedEntity = new \stdClass();

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($primaryEntity);
        $this->context->setForm($form);

        self::assertTrue($this->context->isPrimaryEntityRequest());

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->setPrimaryEntityId(\stdClass::class, 1);
        $includedEntities->setPrimaryEntity($primaryEntity, null);
        $includedEntities->add($includedEntity, \stdClass::class, 2, new IncludedEntityData('0', 0));
        $this->context->setIncludedEntities($includedEntities);
        self::assertTrue($this->context->isPrimaryEntityRequest());
    }

    public function testIsPrimaryEntityRequestForIncludedEntityRequest()
    {
        $primaryEntity = new \stdClass();
        $includedEntity = new \stdClass();

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($includedEntity);
        $this->context->setForm($form);

        self::assertTrue($this->context->isPrimaryEntityRequest());

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->setPrimaryEntityId(\stdClass::class, 1);
        $includedEntities->setPrimaryEntity($primaryEntity, null);
        $includedEntities->add($includedEntity, \stdClass::class, 2, new IncludedEntityData('0', 0));
        $this->context->setIncludedEntities($includedEntities);
        self::assertFalse($this->context->isPrimaryEntityRequest());
    }

    public function testGetAllEntitiesWhenNoIncludedEntities()
    {
        $mainEntity = new \stdClass();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($mainEntity);
        $this->context->setForm($form);

        self::assertSame([$mainEntity], $this->context->getAllEntities());
        self::assertSame([$mainEntity], $this->context->getAllEntities(true));
    }

    public function testGetAllEntitiesWhenNoIncludedEntitiesAndNoMainEntity()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn(null);
        $this->context->setForm($form);

        self::assertSame([], $this->context->getAllEntities());
        self::assertSame([], $this->context->getAllEntities(true));
    }

    public function testGetAllEntitiesWithIncludedEntitiesForPrimaryEntityRequest()
    {
        $primaryEntity = new \stdClass();
        $includedEntity = new \stdClass();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($primaryEntity);
        $this->context->setForm($form);

        $this->context->setIncludedEntities(new IncludedEntityCollection());
        $this->context->getIncludedEntities()
            ->setPrimaryEntityId(\stdClass::class, 123);
        $this->context->getIncludedEntities()
            ->setPrimaryEntity($primaryEntity, null);
        $this->context->getIncludedEntities()
            ->add($includedEntity, \stdClass::class, 1, $this->createMock(IncludedEntityData::class));
        self::assertSame([$primaryEntity, $includedEntity], $this->context->getAllEntities());
        self::assertSame([$primaryEntity], $this->context->getAllEntities(true));
    }

    public function testGetAllEntitiesWithIncludedEntitiesForIncludedEntityRequest()
    {
        $primaryEntity = new \stdClass();
        $includedEntity = new \stdClass();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($includedEntity);
        $this->context->setForm($form);

        $this->context->setIncludedEntities(new IncludedEntityCollection());
        $this->context->getIncludedEntities()
            ->setPrimaryEntityId(\stdClass::class, 123);
        $this->context->getIncludedEntities()
            ->setPrimaryEntity($primaryEntity, null);
        $this->context->getIncludedEntities()
            ->add($includedEntity, \stdClass::class, 1, $this->createMock(IncludedEntityData::class));
        self::assertSame([$primaryEntity, $includedEntity], $this->context->getAllEntities());
        self::assertSame([$includedEntity], $this->context->getAllEntities(true));
    }

    public function testGetAllEntitiesWithIncludedEntitiesAndNoPrimaryEntityForPrimaryEntityRequest()
    {
        $includedEntity = new \stdClass();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn(null);
        $this->context->setForm($form);

        $this->context->setIncludedEntities(new IncludedEntityCollection());
        $this->context->getIncludedEntities()
            ->add($includedEntity, \stdClass::class, 1, $this->createMock(IncludedEntityData::class));
        self::assertSame([$includedEntity], $this->context->getAllEntities());
        self::assertSame([], $this->context->getAllEntities(true));
    }

    public function testGetAllEntitiesWithIncludedEntitiesAndNoPrimaryEntityForIncludedEntityRequest()
    {
        $includedEntity = new \stdClass();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($includedEntity);
        $this->context->setForm($form);

        $this->context->setIncludedEntities(new IncludedEntityCollection());
        $this->context->getIncludedEntities()
            ->add($includedEntity, \stdClass::class, 1, $this->createMock(IncludedEntityData::class));
        self::assertSame([$includedEntity], $this->context->getAllEntities());
        self::assertSame([$includedEntity], $this->context->getAllEntities(true));
    }

    public function testEvent()
    {
        self::assertNull($this->context->getPropertyPath());

        $eventName = 'test_event';
        $this->context->setEvent($eventName);
        self::assertEquals($eventName, $this->context->getEvent());
        self::assertEquals($eventName, $this->context->getFirstGroup());
        self::assertEquals($eventName, $this->context->getLastGroup());
    }

    public function testParentAction()
    {
        self::assertNull($this->context->getPropertyPath());

        $actionName = 'test_action';
        $this->context->setParentAction($actionName);
        self::assertEquals($actionName, $this->context->getParentAction());

        $this->context->setParentAction(null);
        self::assertNull($this->context->getPropertyPath());
    }

    public function testForm()
    {
        $form = $this->createMock(FormInterface::class);
        $this->context->setForm($form);
        self::assertSame($form, $this->context->getForm());
    }

    public function testFindForm()
    {
        $entity = new \stdClass();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($entity);

        $this->context->setForm($form);

        self::assertSame($form, $this->context->findForm($entity));
        self::assertNull($this->context->findForm(new \stdClass()));

        $includedEntities = new IncludedEntityCollection();
        $includedEntity1 = new \stdClass();
        $includedEntity1Form = $this->createMock(FormInterface::class);
        $includedEntity1Data = new IncludedEntityData('0', 0);
        $includedEntity1Data->setForm($includedEntity1Form);
        $includedEntities->add($includedEntity1, \stdClass::class, 1, $includedEntity1Data);
        $includedEntity2 = new \stdClass();
        $includedEntities->add($includedEntity2, \stdClass::class, 2, new IncludedEntityData('1', 1));

        $this->context->setIncludedEntities($includedEntities);

        self::assertSame($includedEntity1Form, $this->context->findForm($includedEntity1));
        self::assertNull($this->context->findForm($includedEntity2));
        self::assertSame($form, $this->context->findForm($entity));
        self::assertNull($this->context->findForm(new \stdClass()));
    }

    public function testFindFormFieldWhenFieldDoesNotExist()
    {
        $propertyPath = 'test';
        $form = new Form($this->getFormConfig('root', true));
        $form->add(new Form($this->getFormConfig('field1')));

        $this->context->setForm($form);
        self::assertNull($this->context->findFormField($propertyPath));
        self::assertNull($this->context->findFormFieldName($propertyPath));
    }

    public function testFindFormFieldWhenFieldDoesNotExistAndExistFormFieldWithSameNameButMappedToAnotherProperty()
    {
        $propertyPath = 'test';
        $form = new Form($this->getFormConfig('root', true));
        $form->add(new Form($this->getFormConfig('field1')));
        $form->add(new Form($this->getFormConfig($propertyPath, false, 'another')));

        $this->context->setForm($form);
        self::assertNull($this->context->findFormField($propertyPath));
        self::assertNull($this->context->findFormFieldName($propertyPath));
    }

    public function testFindFormFieldForNotRenamedField()
    {
        $propertyPath = 'test';
        $form = new Form($this->getFormConfig('root', true));
        $formField = new Form($this->getFormConfig($propertyPath));
        $form->add(new Form($this->getFormConfig('field1')));
        $form->add($formField);

        $this->context->setForm($form);
        self::assertSame($formField, $this->context->findFormField($propertyPath));
        self::assertSame($propertyPath, $this->context->findFormFieldName($propertyPath));
    }

    public function testFindFormFieldForRenamedField()
    {
        $fieldName = 'renamedTest';
        $propertyPath = 'test';
        $form = new Form($this->getFormConfig('root', true));
        $formField = new Form($this->getFormConfig($fieldName, false, $propertyPath));
        $form->add(new Form($this->getFormConfig('field1')));
        $form->add(new Form($this->getFormConfig($propertyPath, false, 'another')));
        $form->add($formField);

        $this->context->setForm($form);
        self::assertSame($formField, $this->context->findFormField($propertyPath));
        self::assertSame($fieldName, $this->context->findFormFieldName($propertyPath));
    }

    public function testDataAndResult()
    {
        self::assertNull($this->context->getData());
        self::assertNull($this->context->getResult());
        self::assertTrue($this->context->hasResult());

        $data = ['key' => 'value'];
        $this->context->setData($data);
        self::assertSame($data, $this->context->getData());
        self::assertSame($data, $this->context->getResult());
        self::assertTrue($this->context->hasResult());

        $data = ['key1' => 'value1'];
        $this->context->setResult($data);
        self::assertSame($data, $this->context->getResult());
        self::assertSame($data, $this->context->getData());
        self::assertTrue($this->context->hasResult());
    }

    public function testRemoveResult()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->context->removeResult();
    }

    public function testEntityMapper()
    {
        $entityMapper = $this->createMock(EntityMapper::class);

        self::assertNull($this->context->getEntityMapper());

        $this->context->setEntityMapper($entityMapper);
        self::assertSame($entityMapper, $this->context->getEntityMapper());

        $this->context->setEntityMapper(null);
        self::assertNull($this->context->getEntityMapper());
    }
}
