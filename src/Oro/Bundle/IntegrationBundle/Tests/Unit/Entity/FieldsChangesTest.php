<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\IntegrationBundle\Entity\FieldsChanges;

class FieldsChangesTest extends \PHPUnit\Framework\TestCase
{
    /** @var FieldsChanges */
    private $entity;

    protected function setUp(): void
    {
        $this->entity = new FieldsChanges([]);
    }

    public function testGetId()
    {
        $this->assertNull($this->entity->getId());
    }

    /**
     * @dataProvider entityDataProvider
     */
    public function testSetGet(string $property, mixed $value)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $propertyAccessor->setValue($this->entity, $property, $value);

        $this->assertEquals(
            $value,
            $propertyAccessor->getValue($this->entity, $property)
        );
    }

    public function entityDataProvider(): array
    {
        return [
            'entityClass'       => ['entityClass', \stdClass::class],
            'empty_entityClass' => ['entityClass', null],
            'entityId'          => ['entityId', 1],
            'empty_entityId'    => ['entityId', null],
            'changedFields'     => ['changedFields', ['field']],
        ];
    }

    /**
     * @dataProvider constructDataProvider
     */
    public function testConstruct(array $fields)
    {
        $fieldsChanges = new FieldsChanges($fields);
        $this->assertEquals($fields, $fieldsChanges->getChangedFields());
    }

    public function constructDataProvider(): array
    {
        return [
            [[]],
            [['field']],
        ];
    }
}
