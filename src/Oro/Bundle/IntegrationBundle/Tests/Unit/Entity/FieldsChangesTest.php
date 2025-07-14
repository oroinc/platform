<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\IntegrationBundle\Entity\FieldsChanges;
use PHPUnit\Framework\TestCase;

class FieldsChangesTest extends TestCase
{
    private FieldsChanges $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new FieldsChanges([]);
    }

    public function testGetId(): void
    {
        $this->assertNull($this->entity->getId());
    }

    /**
     * @dataProvider entityDataProvider
     */
    public function testSetGet(string $property, mixed $value): void
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
    public function testConstruct(array $fields): void
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
