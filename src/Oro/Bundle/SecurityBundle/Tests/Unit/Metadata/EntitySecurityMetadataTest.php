<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;

class EntitySecurityMetadataTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntitySecurityMetadata */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new EntitySecurityMetadata(
            'SomeType',
            'SomeClass',
            'SomeGroup',
            'SomeLabel',
            array(), //permissions
            'SomeDescription',
            'SomeCategory',
            [
                'first' => new FieldSecurityMetadata('first', 'First Label'),
                'second' => new FieldSecurityMetadata('second', 'Second Label', ['VIEW'])
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
        static::assertEquals('SomeClass', $this->entity->getClassName());
        static::assertEquals('SomeGroup', $this->entity->getGroup());
        static::assertEquals('SomeLabel', $this->entity->getLabel());
        static::assertEquals('SomeDescription', $this->entity->getDescription());
        static::assertEquals('SomeCategory', $this->entity->getCategory());
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
        static::assertEquals('SomeClass', $emptyEntity->getClassName());
        static::assertEquals('SomeGroup', $emptyEntity->getGroup());
        static::assertEquals('SomeLabel', $emptyEntity->getLabel());
        static::assertEquals('SomeDescription', $emptyEntity->getDescription());
        static::assertEquals('SomeCategory', $emptyEntity->getCategory());
        $fields = $emptyEntity->getFields();
        static::assertCount(2, $fields);
        static::assertEquals(new FieldSecurityMetadata('first', 'First Label'), $fields['first']);
        static::assertEquals(new FieldSecurityMetadata('second', 'Second Label', ['VIEW']), $fields['second']);
    }
}
