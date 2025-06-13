<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\ComplexData\DataAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor\ComplexDataConvertationDataAccessor;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor\ComplexDataConvertationEntityLoaderInterface;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures\TestEntity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ComplexDataConvertationDataAccessorTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private ComplexDataConvertationEntityLoaderInterface&MockObject $entityLoader;
    private EnumOptionsProvider&MockObject $enumOptionsProvider;
    private ComplexDataConvertationDataAccessor $dataAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityLoader = $this->createMock(ComplexDataConvertationEntityLoaderInterface::class);
        $this->enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);

        $this->dataAccessor = new ComplexDataConvertationDataAccessor(
            $this->doctrineHelper,
            PropertyAccess::createPropertyAccessor(),
            $this->entityLoader,
            $this->enumOptionsProvider
        );
    }

    public function testGetFieldValue(): void
    {
        $entity = new TestEntity();
        $entity->setId(123);

        self::assertSame(123, $this->dataAccessor->getFieldValue($entity, 'id'));
    }

    public function testGetFieldValueForNotExistingField(): void
    {
        $entity = new TestEntity();
        $entity->setId(123);

        self::assertNull(
            $this->dataAccessor->getFieldValue($entity, 'notExisting')
        );
    }

    public function testGetLookupFieldValue(): void
    {
        $entity = new TestEntity();
        $entity->setId(123);
        $entity->setName('Test');

        $this->doctrineHelper->expects(self::never())
            ->method('getSingleEntityIdentifierFieldName');

        self::assertSame('Test', $this->dataAccessor->getLookupFieldValue($entity, 'name', TestEntity::class));
    }

    public function testGetLookupFieldValueWhenNoLookupFieldName(): void
    {
        $entity = new TestEntity();
        $entity->setId(123);
        $entity->setName('Test');

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(TestEntity::class, true)
            ->willReturn('id');

        self::assertSame(123, $this->dataAccessor->getLookupFieldValue($entity, null, TestEntity::class));
    }

    public function testGetLookupFieldValueWhenNoLookupFieldNameAndNoEntityClass(): void
    {
        $entity = new TestEntity();
        $entity->setId(123);
        $entity->setName('Test');

        $this->doctrineHelper->expects(self::never())
            ->method('getSingleEntityIdentifierFieldName');

        self::assertSame(123, $this->dataAccessor->getLookupFieldValue($entity, null, null));
    }

    public function testGetLookupFieldValueForNotExistingField(): void
    {
        $entity = new TestEntity();
        $entity->setId(123);
        $entity->setName('Test');

        $this->doctrineHelper->expects(self::never())
            ->method('getSingleEntityIdentifierFieldName');

        self::assertNull(
            $this->dataAccessor->getLookupFieldValue($entity, 'notExisting', TestEntity::class)
        );
    }

    public function testGetLookupFieldValueForEnumEntityWhenLookupFieldNameIsName(): void
    {
        $entity = new EnumOption('test_enum', 'Item 1', 'item_1');

        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumInternalChoices')
            ->with('test_enum')
            ->willReturn(['item_1' => 'Translated Item 1', 'item_2' => 'Translated Item 2']);

        self::assertSame(
            'Translated Item 1',
            $this->dataAccessor->getLookupFieldValue($entity, 'name', 'Extend\Entity\EV_Test_Enum')
        );
    }

    public function testGetLookupFieldValueForEnumEntityWhenLookupFieldNameIsNameAndNoTranslatedItem(): void
    {
        $entity = new EnumOption('test_enum', 'Item 3', 'item_3');

        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumInternalChoices')
            ->with('test_enum')
            ->willReturn(['item_1' => 'Translated Item 1', 'item_2' => 'Translated Item 2']);

        self::assertNull(
            $this->dataAccessor->getLookupFieldValue($entity, 'name', 'Extend\Entity\EV_Test_Enum')
        );
    }

    public function testGetLookupFieldValueForEnumEntityWhenNoLookupFieldName(): void
    {
        $entity = new EnumOption('test_enum', 'Item 1', 'item_1');

        $this->enumOptionsProvider->expects(self::never())
            ->method('getEnumInternalChoices');

        self::assertSame(
            'item_1',
            $this->dataAccessor->getLookupFieldValue($entity, null, 'Extend\Entity\EV_Test_Enum')
        );
    }

    public function testGetLookupFieldValueForEnumEntityWhenLookupFieldNameIsId(): void
    {
        $entity = new EnumOption('test_enum', 'Item 1', 'item_1');

        $this->enumOptionsProvider->expects(self::never())
            ->method('getEnumInternalChoices');

        self::assertSame(
            'item_1',
            $this->dataAccessor->getLookupFieldValue($entity, 'id', 'Extend\Entity\EV_Test_Enum')
        );
    }

    public function testGetLookupFieldValueForEnumEntityWhenLookupFieldNameIsInternalId(): void
    {
        $entity = new EnumOption('test_enum', 'Item 1', 'item_1');

        $this->enumOptionsProvider->expects(self::never())
            ->method('getEnumInternalChoices');

        self::assertSame(
            'item_1',
            $this->dataAccessor->getLookupFieldValue($entity, 'internalId', 'Extend\Entity\EV_Test_Enum')
        );
    }

    public function testGetLookupFieldValueForEnumEntityWhenCustomLookupFieldName(): void
    {
        $entity = new EnumOption('test_enum', 'Item 1', 'item_1');

        $this->enumOptionsProvider->expects(self::never())
            ->method('getEnumInternalChoices');

        self::assertSame(
            'test_enum',
            $this->dataAccessor->getLookupFieldValue($entity, 'enumCode', 'Extend\Entity\EV_Test_Enum')
        );
    }

    public function testFindEntityId(): void
    {
        $entity = new TestEntity();

        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(TestEntity::class, ['name' => 'Test'])
            ->willReturn($entity);
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with(self::identicalTo($entity))
            ->willReturn(123);

        self::assertSame(123, $this->dataAccessor->findEntityId(TestEntity::class, 'name', 'Test'));
    }

    public function testFindEntity(): void
    {
        $entity = new TestEntity();

        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(TestEntity::class, ['name' => 'Test'])
            ->willReturn($entity);

        self::assertSame($entity, $this->dataAccessor->findEntity(TestEntity::class, 'name', 'Test'));
    }

    public function testFindEntityIdWhenEntityNotFound(): void
    {
        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(TestEntity::class, ['name' => 'Test'])
            ->willReturn(null);
        $this->doctrineHelper->expects(self::never())
            ->method('getSingleEntityIdentifier');

        self::assertNull($this->dataAccessor->findEntityId(TestEntity::class, 'name', 'Test'));
    }

    public function testFindEntityWhenEntityNotFound(): void
    {
        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(TestEntity::class, ['name' => 'Test'])
            ->willReturn(null);

        self::assertNull($this->dataAccessor->findEntity(TestEntity::class, 'name', 'Test'));
    }

    public function testFindEntityIdWhenNoLookupFieldName(): void
    {
        $entity = new TestEntity();

        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(TestEntity::class, ['id' => 'Test'])
            ->willReturn($entity);
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(TestEntity::class)
            ->willReturn('id');
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with(self::identicalTo($entity))
            ->willReturn(123);

        self::assertSame(123, $this->dataAccessor->findEntityId(TestEntity::class, null, 'Test'));
    }

    public function testFindEntityWhenNoLookupFieldName(): void
    {
        $entity = new TestEntity();

        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(TestEntity::class, ['id' => 'Test'])
            ->willReturn($entity);
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(TestEntity::class)
            ->willReturn('id');

        self::assertSame($entity, $this->dataAccessor->findEntity(TestEntity::class, null, 'Test'));
    }

    public function testFindEntityIdForEnumEntityWhenLookupFieldNameIsName(): void
    {
        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumInternalChoices')
            ->with('test_enum')
            ->willReturn(['item_1' => 'Translated Item 1', 'item_2' => 'Translated Item 2']);

        $this->entityLoader->expects(self::never())
            ->method('loadEntity');

        self::assertSame(
            'item_1',
            $this->dataAccessor->findEntityId('Extend\Entity\EV_Test_Enum', 'name', 'Translated Item 1')
        );
    }

    public function testFindEntityForEnumEntityWhenLookupFieldNameIsName(): void
    {
        $entity = new EnumOption('test_enum', 'Item 1', 'item_1');

        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumInternalChoices')
            ->with('test_enum')
            ->willReturn(['item_1' => 'Translated Item 1', 'item_2' => 'Translated Item 2']);

        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(EnumOption::class, ['enumCode' => 'test_enum', 'internalId' => 'item_1'])
            ->willReturn($entity);

        self::assertSame(
            $entity,
            $this->dataAccessor->findEntity('Extend\Entity\EV_Test_Enum', 'name', 'Translated Item 1')
        );
    }

    public function testFindEntityForEnumEntityWhenLookupFieldNameIsNameAndEntityNotFound(): void
    {
        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumInternalChoices')
            ->with('test_enum')
            ->willReturn(['item_1' => 'Translated Item 1', 'item_2' => 'Translated Item 2']);

        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(EnumOption::class, ['enumCode' => 'test_enum', 'internalId' => 'item_1'])
            ->willReturn(null);

        self::assertNull(
            $this->dataAccessor->findEntity('Extend\Entity\EV_Test_Enum', 'name', 'Translated Item 1')
        );
    }

    public function testFindEntityIdForEnumEntityWhenLookupFieldNameIsNameAndNoTranslatedItem(): void
    {
        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumInternalChoices')
            ->with('test_enum')
            ->willReturn(['item_1' => 'Translated Item 1', 'item_2' => 'Translated Item 2']);

        $this->entityLoader->expects(self::never())
            ->method('loadEntity');

        self::assertNull(
            $this->dataAccessor->findEntityId('Extend\Entity\EV_Test_Enum', 'name', 'Translated Item 3')
        );
    }

    public function testFindEntityForEnumEntityWhenLookupFieldNameIsNameAndNoTranslatedItem(): void
    {
        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumInternalChoices')
            ->with('test_enum')
            ->willReturn(['item_1' => 'Translated Item 1', 'item_2' => 'Translated Item 2']);

        $this->entityLoader->expects(self::never())
            ->method('loadEntity');

        self::assertNull(
            $this->dataAccessor->findEntity('Extend\Entity\EV_Test_Enum', 'name', 'Translated Item 3')
        );
    }

    public function testFindEntityIdForEnumEntityWhenNoLookupFieldName(): void
    {
        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumInternalChoices')
            ->with('test_enum')
            ->willReturn(['item_1' => 'Translated Item 1', 'item_2' => 'Translated Item 2']);

        $this->entityLoader->expects(self::never())
            ->method('loadEntity');

        self::assertSame(
            'item_1',
            $this->dataAccessor->findEntityId('Extend\Entity\EV_Test_Enum', null, 'item_1')
        );
    }

    public function testFindEntityForEnumEntityWhenNoLookupFieldName(): void
    {
        $entity = new EnumOption('test_enum', 'Item 1', 'item_1');

        $this->enumOptionsProvider->expects(self::never())
            ->method('getEnumInternalChoices');

        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(EnumOption::class, ['enumCode' => 'test_enum', 'internalId' => 'item_1'])
            ->willReturn($entity);

        self::assertSame(
            $entity,
            $this->dataAccessor->findEntity('Extend\Entity\EV_Test_Enum', null, 'item_1')
        );
    }

    public function testFindEntityIdForEnumEntityWhenNoLookupFieldNameAndEntityNotFound(): void
    {
        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumInternalChoices')
            ->with('test_enum')
            ->willReturn(['item_1' => 'Translated Item 1', 'item_2' => 'Translated Item 2']);

        $this->entityLoader->expects(self::never())
            ->method('loadEntity');

        self::assertNull(
            $this->dataAccessor->findEntityId('Extend\Entity\EV_Test_Enum', null, 'item_3')
        );
    }

    public function testFindEntityForEnumEntityWhenNoLookupFieldNameAndEntityNotFound(): void
    {
        $this->enumOptionsProvider->expects(self::never())
            ->method('getEnumInternalChoices');

        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(EnumOption::class, ['enumCode' => 'test_enum', 'internalId' => 'item_3'])
            ->willReturn(null);

        self::assertNull(
            $this->dataAccessor->findEntity('Extend\Entity\EV_Test_Enum', null, 'item_3')
        );
    }

    public function testFindEntityIdForEnumEntityWhenLookupFieldNameIsId(): void
    {
        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumInternalChoices')
            ->with('test_enum')
            ->willReturn(['item_1' => 'Translated Item 1', 'item_2' => 'Translated Item 2']);

        $this->entityLoader->expects(self::never())
            ->method('loadEntity');

        self::assertSame(
            'item_1',
            $this->dataAccessor->findEntityId('Extend\Entity\EV_Test_Enum', 'id', 'item_1')
        );
    }

    public function testFindEntityForEnumEntityWhenLookupFieldNameIsId(): void
    {
        $entity = new EnumOption('test_enum', 'Item 1', 'item_1');

        $this->enumOptionsProvider->expects(self::never())
            ->method('getEnumInternalChoices');

        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(EnumOption::class, ['enumCode' => 'test_enum', 'internalId' => 'item_1'])
            ->willReturn($entity);

        self::assertSame(
            $entity,
            $this->dataAccessor->findEntity('Extend\Entity\EV_Test_Enum', 'id', 'item_1')
        );
    }

    public function testFindEntityIdForEnumEntityWhenLookupFieldNameIsInternalId(): void
    {
        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumInternalChoices')
            ->with('test_enum')
            ->willReturn(['item_1' => 'Translated Item 1', 'item_2' => 'Translated Item 2']);

        $this->entityLoader->expects(self::never())
            ->method('loadEntity');

        self::assertSame(
            'item_1',
            $this->dataAccessor->findEntityId('Extend\Entity\EV_Test_Enum', 'internalId', 'item_1')
        );
    }

    public function testFindEntityForEnumEntityWhenLookupFieldNameIsInternalId(): void
    {
        $entity = new EnumOption('test_enum', 'Item 1', 'item_1');

        $this->enumOptionsProvider->expects(self::never())
            ->method('getEnumInternalChoices');

        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(EnumOption::class, ['enumCode' => 'test_enum', 'internalId' => 'item_1'])
            ->willReturn($entity);

        self::assertSame(
            $entity,
            $this->dataAccessor->findEntity('Extend\Entity\EV_Test_Enum', 'internalId', 'item_1')
        );
    }

    public function testFindEntityIdForEnumEntityWhenCustomLookupFieldName(): void
    {
        $entity = new EnumOption('test_enum', 'Item 1', 'item_1');

        $this->enumOptionsProvider->expects(self::never())
            ->method('getEnumInternalChoices');

        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(EnumOption::class, ['enumCode' => 'test_enum', 'otherField' => 'test_value_1'])
            ->willReturn($entity);

        self::assertSame(
            'item_1',
            $this->dataAccessor->findEntityId('Extend\Entity\EV_Test_Enum', 'otherField', 'test_value_1')
        );
    }

    public function testFindEntityForEnumEntityWhenCustomLookupFieldName(): void
    {
        $entity = new EnumOption('test_enum', 'Item 1', 'item_1');

        $this->enumOptionsProvider->expects(self::never())
            ->method('getEnumInternalChoices');

        $this->entityLoader->expects(self::once())
            ->method('loadEntity')
            ->with(EnumOption::class, ['enumCode' => 'test_enum', 'otherField' => 'test_value_1'])
            ->willReturn($entity);

        self::assertSame(
            $entity,
            $this->dataAccessor->findEntity('Extend\Entity\EV_Test_Enum', 'otherField', 'test_value_1')
        );
    }
}
