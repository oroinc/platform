<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;

class EntitySecurityMetadataTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntitySecurityMetadata */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new EntitySecurityMetadata(
            'SomeType',
            \stdClass::class,
            'SomeGroup',
            'SomeLabel',
            array(), //permissions
            'SomeDescription',
            'SomeCategory',
            [
                'first' => new FieldSecurityMetadata('first', 'First Label'),
                'second' => new FieldSecurityMetadata('second', 'Second Label', ['VIEW'], 'Second Description')
            ]
        );
    }

    protected function tearDown()
    {
        unset($this->entity);
    }

    public function testGetters()
    {
        static::assertEquals('SomeType', $this->entity->getSecurityType());
        static::assertEquals(\stdClass::class, $this->entity->getClassName());
        static::assertEquals('SomeGroup', $this->entity->getGroup());
        static::assertEquals('SomeLabel', $this->entity->getLabel());
        static::assertEquals('SomeDescription', $this->entity->getDescription());
        static::assertEquals('SomeCategory', $this->entity->getCategory());
        static::assertFalse($this->entity->isTranslated());
        $fields = $this->entity->getFields();
        static::assertCount(2, $fields);
        static::assertEquals(new FieldSecurityMetadata('first', 'First Label'), $fields['first']);
        $secondFieldConfig =  $fields['second'];
        static::assertEquals('second', $secondFieldConfig->getFieldName());
        static::assertEquals('Second Label', $secondFieldConfig->getLabel());
        static::assertEquals(['VIEW'], $secondFieldConfig->getPermissions());
    }

    public function testSerialize()
    {
        $data        = serialize($this->entity);
        $emptyEntity = unserialize($data);

        static::assertEquals('SomeType', $emptyEntity->getSecurityType());
        static::assertEquals(\stdClass::class, $emptyEntity->getClassName());
        static::assertEquals('SomeGroup', $emptyEntity->getGroup());
        static::assertEquals('SomeLabel', $emptyEntity->getLabel());
        static::assertEquals('SomeDescription', $emptyEntity->getDescription());
        static::assertEquals('SomeCategory', $emptyEntity->getCategory());
        $fields = $emptyEntity->getFields();
        static::assertCount(2, $fields);
        static::assertEquals(new FieldSecurityMetadata('first', 'First Label'), $fields['first']);
        static::assertEquals(
            new FieldSecurityMetadata('second', 'Second Label', ['VIEW'], 'Second Description'),
            $fields['second']
        );
    }

    public function testSetters()
    {
        $label = 'SomeAnotherLabel';
        $this->entity->setLabel($label);
        static::assertEquals($label, $this->entity->getLabel());

        $description = 'SomeAnotherDescription';
        $this->entity->setDescription($description);
        static::assertEquals($description, $this->entity->getDescription());

        $this->entity->setTranslated(true);
        static::assertTrue($this->entity->isTranslated());

        $fields = [new FieldSecurityMetadata('anotherField', 'AnotherFieldLabel')];
        $this->entity->setFields($fields);
        static::assertEquals($fields, $this->entity->getFields());
    }
}
