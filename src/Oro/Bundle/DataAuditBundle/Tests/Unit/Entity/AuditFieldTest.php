<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Entity;

use DateTime;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Stub\AuditField;

class AuditFieldTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        AuditFieldTypeRegistry::addType('testingtype', 'testingtype');
    }

    public function teardown()
    {
        AuditFieldTypeRegistry::removeType('testingtype');
    }

    /**
     * @dataProvider provider
     */
    public function testAuditField($field, $dataType, $newValue, $oldValue, $expectedDataType)
    {
        $auditField = new AuditField($field, $dataType, $newValue, $oldValue);
        $auditField->setTranslationDomain('message');
        $this->assertEquals($expectedDataType, $auditField->getDataType());
        $this->assertEquals($field, $auditField->getField());
        $this->assertEquals($newValue, $auditField->getNewValue());
        $this->assertEquals($oldValue, $auditField->getOldValue());
        $this->assertEquals('message', $auditField->getTranslationDomain());
    }

    public function provider()
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
            ['field', 'date', new DateTime('2014-01-05'), new DateTime('2014-01-07'), 'date'],
            ['field', 'time', new DateTime('13:22:15'), new DateTime('13:32:15'), 'time'],
            [
                'field',
                'datetime',
                new DateTime('2014-01-05 13:22:15'),
                new DateTime('2014-01-07 13:34:07'),
                'datetime'
            ],
            ['field', 'testingtype', 'old', 'new', 'testingtype'],
        ];
    }

    public function testShouldAllowAddEntityRemovedFromCollection()
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityRemovedFromCollection(TestAuditDataOwner::class, 'theId', 'theName');
        $field->calculateNewValue();

        $this->assertEquals(null, $field->getOldValue());
        $this->assertEquals("\nRemoved: theName", $field->getNewValue());
    }

    public function testShouldAllowAddSomeEntitiesRemovedFromCollection()
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityRemovedFromCollection(TestAuditDataOwner::class, 'theId', 'theName');
        $field->addEntityRemovedFromCollection(TestAuditDataOwner::class, 'theAnotherId', 'theAnotherName');
        $field->calculateNewValue();

        $this->assertEquals(null, $field->getOldValue());
        $this->assertEquals("\nRemoved: theName, theAnotherName", $field->getNewValue());
    }

    public function testShouldAllowAddEntityAddedToCollection()
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityAddedToCollection(TestAuditDataOwner::class, 'theId', 'theName');
        $field->calculateNewValue();

        $this->assertEquals(null, $field->getOldValue());
        $this->assertEquals('Added: theName', $field->getNewValue());
    }

    public function testShouldAllowAddSomeEntitiesAddedToCollection()
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityAddedToCollection(TestAuditDataOwner::class, 'theId', 'theName');
        $field->addEntityAddedToCollection(TestAuditDataOwner::class, 'theAnotherId', 'theAnotherName');
        $field->calculateNewValue();

        $this->assertEquals(null, $field->getOldValue());
        $this->assertEquals('Added: theName, theAnotherName', $field->getNewValue());
    }

    public function testShouldAllowAddSomeEntitiesAsChangedToCollection()
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityChangedInCollection(TestAuditDataOwner::class, 'theId', 'theName');
        $field->addEntityChangedInCollection(TestAuditDataOwner::class, 'theAnotherId', 'theAnotherName');
        $field->calculateNewValue();

        $this->assertEquals(null, $field->getOldValue());
        $this->assertEquals("\nChanged: theName, theAnotherName", $field->getNewValue());
    }

    public function testShouldMergeEmptyCollectionFields()
    {
        $field = new AuditField('field', 'text', null, null);

        $anotherField = new AuditField('field', 'text', null, null);

        $field->mergeCollectionField($anotherField);
        $field->calculateNewValue();

        $this->assertEquals(null, $field->getOldValue());
        $this->assertEquals(null, $field->getNewValue());
    }

    public function testShouldMergeEmptyCollectionFieldWithNotEmptyOne()
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityAddedToCollection(TestAuditDataOwner::class, 'theId', 'theName');
        $field->addEntityRemovedFromCollection(TestAuditDataOwner::class, 'theAnotherId', 'theAnotherName');

        $anotherField = new AuditField('field', 'text', null, null);

        $field->mergeCollectionField($anotherField);
        $field->calculateNewValue();

        $this->assertEquals(null, $field->getOldValue());
        $this->assertEquals("Added: theName\nRemoved: theAnotherName", $field->getNewValue());
    }

    public function testShouldMergeNotEmptyCollectionFields()
    {
        $field = new AuditField('field', 'text', null, null);
        $field->addEntityAddedToCollection(TestAuditDataOwner::class, 'theId', 'theName');
        $field->addEntityRemovedFromCollection(TestAuditDataOwner::class, 'theAnotherId', 'theAnotherName');

        $anotherField = new AuditField('field', 'text', null, null);
        $anotherField->addEntityAddedToCollection(TestAuditDataOwner::class, 'theId', 'theFooName');
        $anotherField->addEntityRemovedFromCollection(TestAuditDataOwner::class, 'theAnotherId', 'theBarName');

        $field->mergeCollectionField($anotherField);
        $field->calculateNewValue();

        $this->assertEquals(null, $field->getOldValue());
        $this->assertEquals("Added: theName, theFooName\nRemoved: theAnotherName, theBarName", $field->getNewValue());
    }
}
