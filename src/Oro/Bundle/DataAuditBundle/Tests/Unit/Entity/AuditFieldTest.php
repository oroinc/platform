<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Entity;

use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Fixture\LoggableClass as TestEntity;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Stub\AuditField;
use PHPUnit\Framework\TestCase;

class AuditFieldTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        AuditFieldTypeRegistry::addType('testingtype', 'testingtype');
    }

    #[\Override]
    protected function tearDown(): void
    {
        AuditFieldTypeRegistry::removeType('testingtype');
    }

    /**
     * @dataProvider auditFieldDataProvider
     */
    public function testAuditField(
        string $field,
        string $dataType,
        mixed $newValue,
        mixed $oldValue,
        string $expectedDataType
    ): void {
        $auditField = new AuditField($field, $dataType, $newValue, $oldValue);
        $auditField->setTranslationDomain('message');
        $this->assertEquals($expectedDataType, $auditField->getDataType());
        $this->assertEquals($field, $auditField->getField());
        $this->assertEquals($newValue, $auditField->getNewValue());
        $this->assertEquals($oldValue, $auditField->getOldValue());
        $this->assertEquals('message', $auditField->getTranslationDomain());
    }

    public function auditFieldDataProvider(): array
    {
        return [
            ['field', 'boolean', true, false, 'boolean'],
            ['field', 'smallint', 1, 0, 'integer'],
            ['field', 'integer', 1, 0, 'integer'],
            ['field', 'float', 1.5, 3.2, 'float'],
            ['field', 'decimal', 1.5, 3.2, 'float'],
            ['field', 'text', 'new', 'old', 'text'],
            ['field', 'string', 'new', 'old', 'text'],
            ['field', 'guid', 'new', 'old', 'text'],
            ['field', 'date', new \DateTime('2014-01-05'), new \DateTime('2014-01-07'), 'date'],
            ['field', 'time', new \DateTime('13:22:15'), new \DateTime('13:32:15'), 'time'],
            [
                'field',
                'datetime',
                new \DateTime('2014-01-05 13:22:15'),
                new \DateTime('2014-01-07 13:34:07'),
                'datetime'
            ],
            ['field', 'testingtype', 'old', 'new', 'testingtype'],
            ['field', 'json', [1, 2, 'foo', ['a' => 'b', 'k' => 'l']], [1, 3, 'bar', ['a' => 'c', 'x' => 'y']], 'json'],
        ];
    }

    public function testShouldAllowAddEntityRemovedFromCollection(): void
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityRemovedFromCollection(TestEntity::class, 1, 'theName');
        $field->calculateNewValue();

        $this->assertEquals('Removed: theName', $field->getOldValue());
        $this->assertEquals(null, $field->getNewValue());
    }

    public function testShouldAllowAddSomeEntitiesRemovedFromCollection(): void
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityRemovedFromCollection(TestEntity::class, 1, 'theName');
        $field->addEntityRemovedFromCollection(TestEntity::class, 2, 'theAnotherName');
        $field->calculateNewValue();

        $this->assertEquals('Removed: theName, theAnotherName', $field->getOldValue());
        $this->assertEquals(null, $field->getNewValue());
    }

    public function testShouldAllowAddEntityAddedToCollection(): void
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityAddedToCollection(TestEntity::class, 1, 'theName');
        $field->calculateNewValue();

        $this->assertEquals(null, $field->getOldValue());
        $this->assertEquals('Added: theName', $field->getNewValue());
    }

    public function testShouldAllowAddSomeEntitiesAddedToCollection(): void
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityAddedToCollection(TestEntity::class, 1, 'theName');
        $field->addEntityAddedToCollection(TestEntity::class, 2, 'theAnotherName');
        $field->calculateNewValue();

        $this->assertEquals(null, $field->getOldValue());
        $this->assertEquals('Added: theName, theAnotherName', $field->getNewValue());
    }

    public function testShouldAllowAddSomeEntitiesAsChangedToCollection(): void
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityChangedInCollection(TestEntity::class, 1, 'theName');
        $field->addEntityChangedInCollection(TestEntity::class, 2, 'theAnotherName');
        $field->calculateNewValue();

        $this->assertEquals(null, $field->getOldValue());
        $this->assertEquals("\nChanged: theName, theAnotherName", $field->getNewValue());
    }

    public function testShouldMergeEmptyCollectionFields(): void
    {
        $field = new AuditField('field', 'text', null, null);

        $anotherField = new AuditField('field', 'text', null, null);

        $field->mergeCollectionField($anotherField);
        $field->calculateNewValue();

        $this->assertEquals(null, $field->getOldValue());
        $this->assertEquals(null, $field->getNewValue());
    }

    public function testShouldMergeEmptyCollectionFieldWithNotEmptyOne(): void
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityAddedToCollection(TestEntity::class, 1, 'theName');
        $field->addEntityRemovedFromCollection(TestEntity::class, 2, 'theAnotherName');

        $anotherField = new AuditField('field', 'text', null, null);

        $field->mergeCollectionField($anotherField);
        $field->calculateNewValue();

        $this->assertEquals('Removed: theAnotherName', $field->getOldValue());
        $this->assertEquals('Added: theName', $field->getNewValue());
    }

    public function testShouldMergeNotEmptyCollectionFields(): void
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityAddedToCollection(TestEntity::class, 1, 'theName');
        $field->addEntityRemovedFromCollection(TestEntity::class, 2, 'theAnotherName');

        $anotherField = new AuditField('field', 'text', null, null);
        $anotherField->addEntityAddedToCollection(TestEntity::class, 3, 'theFooName');
        $anotherField->addEntityRemovedFromCollection(TestEntity::class, 4, 'theBarName');

        $field->mergeCollectionField($anotherField);
        $field->calculateNewValue();

        $this->assertEquals('Removed: theAnotherName, theBarName', $field->getOldValue());
        $this->assertEquals('Added: theName, theFooName', $field->getNewValue());
    }
}
