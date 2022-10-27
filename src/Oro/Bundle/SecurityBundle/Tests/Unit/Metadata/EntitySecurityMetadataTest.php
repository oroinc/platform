<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;

class EntitySecurityMetadataTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntitySecurityMetadata */
    private $entity;

    protected function setUp(): void
    {
        $this->entity = new EntitySecurityMetadata(
            'SomeType',
            \stdClass::class,
            'SomeGroup',
            'SomeLabel',
            [], //permissions
            'SomeDescription',
            'SomeCategory',
            [
                'first'  => new FieldSecurityMetadata('first', 'First Label'),
                'second' => new FieldSecurityMetadata('second', 'Second Label', ['VIEW'], 'Second Description')
            ]
        );
    }

    public function testGetters()
    {
        self::assertEquals('SomeType', $this->entity->getSecurityType());
        self::assertEquals(\stdClass::class, $this->entity->getClassName());
        self::assertEquals('SomeGroup', $this->entity->getGroup());
        self::assertEquals('SomeLabel', $this->entity->getLabel());
        self::assertEquals('SomeDescription', $this->entity->getDescription());
        self::assertEquals('SomeCategory', $this->entity->getCategory());
        $fields = $this->entity->getFields();
        self::assertCount(2, $fields);
        self::assertEquals(new FieldSecurityMetadata('first', 'First Label'), $fields['first']);
        $secondFieldConfig = $fields['second'];
        self::assertEquals('second', $secondFieldConfig->getFieldName());
        self::assertEquals('Second Label', $secondFieldConfig->getLabel());
        self::assertEquals(['VIEW'], $secondFieldConfig->getPermissions());
    }

    public function testSerialize()
    {
        $data = serialize($this->entity);
        $emptyEntity = unserialize($data);

        self::assertEquals('SomeType', $emptyEntity->getSecurityType());
        self::assertEquals(\stdClass::class, $emptyEntity->getClassName());
        self::assertEquals('SomeGroup', $emptyEntity->getGroup());
        self::assertEquals('SomeLabel', $emptyEntity->getLabel());
        self::assertEquals('SomeDescription', $emptyEntity->getDescription());
        self::assertEquals('SomeCategory', $emptyEntity->getCategory());
        $fields = $emptyEntity->getFields();
        self::assertCount(2, $fields);
        self::assertEquals(new FieldSecurityMetadata('first', 'First Label'), $fields['first']);
        self::assertEquals(
            new FieldSecurityMetadata('second', 'Second Label', ['VIEW'], 'Second Description'),
            $fields['second']
        );
    }
}
