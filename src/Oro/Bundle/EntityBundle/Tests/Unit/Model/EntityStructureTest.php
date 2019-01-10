<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class EntityStructureTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new EntityStructure(), [
            ['id', 'id'],
            ['label', 'label'],
            ['pluralLabel', 'pluralLabel'],
            ['alias', 'alias'],
            ['pluralAlias', 'pluralAlias'],
            ['className', 'className'],
            ['icon', 'icon'],
            ['fields', [(new EntityFieldStructure())->setName('field1')]],
            ['options', ['option1' => true]],
            ['routes', ['view' => 'route']],
        ]);
    }

    public function testGetNotExistingOption()
    {
        $item = new EntityStructure();
        $this->assertFalse($item->hasOption('unknown'));
        $this->assertNull($item->getOption('unknown'));
    }

    public function testSerialization()
    {
        $entity = new EntityStructure();
        $entity->setId('entity1');
        $entity->setClassName('Test\Entity1');
        $entity->setLabel('Entity 1');
        $entity->setPluralLabel('Entity 1 plural');
        $entity->setAlias('entity1');
        $entity->setPluralAlias('entity1_plural');
        $entity->setIcon('icon1');
        $entity->setRoutes(['view' => 'view_route']);
        $entity->addOption('option1', 'value1');

        $field = new EntityFieldStructure();
        $field->setName('field1');
        $field->setType('integer');
        $field->setLabel('Field 1');
        $field->setRelationType('manyToOne');
        $field->setRelatedEntityName('Test\TargetEntity');
        $field->addOption('option1', 'value1');
        $entity->addField($field);

        $unserialized = unserialize(serialize($entity));
        $this->assertEquals($entity, $unserialized);
        $this->assertNotSame($entity, $unserialized);
    }
}
