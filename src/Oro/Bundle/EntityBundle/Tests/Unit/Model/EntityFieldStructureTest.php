<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;

class EntityFieldStructureTest extends \PHPUnit\Framework\TestCase
{
    public function testName()
    {
        $field = new EntityFieldStructure();
        self::assertNull($field->getName());

        $value = 'test';
        $field->setName($value);
        self::assertSame($value, $field->getName());
    }

    public function testLabel()
    {
        $field = new EntityFieldStructure();
        self::assertNull($field->getLabel());

        $value = 'test';
        $field->setLabel($value);
        self::assertSame($value, $field->getLabel());
    }

    public function testType()
    {
        $field = new EntityFieldStructure();
        self::assertNull($field->getType());

        $value = 'test';
        $field->setType($value);
        self::assertSame($value, $field->getType());
    }

    public function testRelationType()
    {
        $field = new EntityFieldStructure();
        self::assertNull($field->getRelationType());

        $value = 'test';
        $field->setRelationType($value);
        self::assertSame($value, $field->getRelationType());
    }

    public function testRelatedEntityName()
    {
        $field = new EntityFieldStructure();
        self::assertNull($field->getRelatedEntityName());

        $value = 'test';
        $field->setRelatedEntityName($value);
        self::assertSame($value, $field->getRelatedEntityName());
    }

    public function testOptions()
    {
        $field = new EntityFieldStructure();
        self::assertSame([], $field->getOptions());
        self::assertFalse($field->hasOption('option1'));
        self::assertNull($field->getOption('option1'));

        $field->addOption('option1', 'value1');
        self::assertTrue($field->hasOption('option1'));
        self::assertSame('value1', $field->getOption('option1'));
        self::assertSame(['option1' => 'value1'], $field->getOptions());
    }

    public function testSerialization()
    {
        $field = new EntityFieldStructure();
        $field->setName('field1');
        $field->setType('integer');
        $field->setLabel('Field 1');
        $field->setRelationType('manyToOne');
        $field->setRelatedEntityName('Test\TargetEntity');
        $field->addOption('option1', 'value1');

        $unserialized = unserialize(serialize($field));
        $this->assertEquals($field, $unserialized);
        $this->assertNotSame($field, $unserialized);
    }
}
