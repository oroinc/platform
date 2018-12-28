<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class EntityFieldStructureTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new EntityFieldStructure(), [
            ['name', 'name'],
            ['label', 'label'],
            ['type', 'type'],
            ['relationType', 'relationType'],
            ['relatedEntityName', 'relatedEntityName'],
            ['options', ['option1' => true]],
        ]);
    }

    public function testGetNotExistingOption()
    {
        $item = new EntityFieldStructure();
        $this->assertFalse($item->hasOption('unknown'));
        $this->assertNull($item->getOption('unknown'));
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
