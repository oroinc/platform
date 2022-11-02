<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityStructureTest extends \PHPUnit\Framework\TestCase
{
    public function testId()
    {
        $entity = new EntityStructure();
        self::assertNull($entity->getId());

        $value = 'test';
        $entity->setId($value);
        self::assertSame($value, $entity->getId());
    }

    public function testLabel()
    {
        $entity = new EntityStructure();
        self::assertNull($entity->getLabel());

        $value = 'test';
        $entity->setLabel($value);
        self::assertSame($value, $entity->getLabel());
    }

    public function testPluralLabel()
    {
        $entity = new EntityStructure();
        self::assertNull($entity->getPluralLabel());

        $value = 'test';
        $entity->setPluralLabel($value);
        self::assertSame($value, $entity->getPluralLabel());
    }

    public function testAlias()
    {
        $entity = new EntityStructure();
        self::assertNull($entity->getAlias());

        $value = 'test';
        $entity->setAlias($value);
        self::assertSame($value, $entity->getAlias());
    }

    public function testPluralAlias()
    {
        $entity = new EntityStructure();
        self::assertNull($entity->getPluralAlias());

        $value = 'test';
        $entity->setPluralAlias($value);
        self::assertSame($value, $entity->getPluralAlias());
    }

    public function testClassName()
    {
        $entity = new EntityStructure();
        self::assertNull($entity->getClassName());

        $value = 'test';
        $entity->setClassName($value);
        self::assertSame($value, $entity->getClassName());
    }

    public function testIcon()
    {
        $entity = new EntityStructure();
        self::assertNull($entity->getIcon());

        $value = 'test';
        $entity->setIcon($value);
        self::assertSame($value, $entity->getIcon());
    }

    public function testFields()
    {
        $entity = new EntityStructure();
        self::assertSame([], $entity->getFields());

        $field1 = new EntityFieldStructure();
        $field1->setName('field1');
        $entity->setFields([$field1]);
        self::assertSame([$field1], $entity->getFields());

        $field2 = new EntityFieldStructure();
        $field2->setName('field1');
        $entity->addField($field2);
        self::assertSame([$field1, $field2], $entity->getFields());
    }

    public function testOptions()
    {
        $entity = new EntityStructure();
        self::assertSame([], $entity->getOptions());
        self::assertFalse($entity->hasOption('option1'));
        self::assertNull($entity->getOption('option1'));

        $entity->addOption('option1', 'value1');
        self::assertTrue($entity->hasOption('option1'));
        self::assertSame('value1', $entity->getOption('option1'));
        self::assertSame(['option1' => 'value1'], $entity->getOptions());
    }

    public function testRoutes()
    {
        $entity = new EntityStructure();
        self::assertSame([], $entity->getRoutes());

        $value = ['view' => 'route'];
        $entity->setRoutes($value);
        self::assertSame($value, $entity->getRoutes());
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
