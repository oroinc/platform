<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\IntegrationBundle\Entity\FieldsChanges;

class FieldsChangesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldsChanges
     */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new FieldsChanges([]);
    }

    protected function tearDown()
    {
        unset($this->entity);
    }

    public function testGetId()
    {
        $this->assertNull($this->entity->getId());
    }

    /**
     * @param string $property
     * @param mixed  $value
     *
     * @dataProvider entityDataProvider
     */
    public function testSetGet($property, $value)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $propertyAccessor->setValue($this->entity, $property, $value);

        $this->assertEquals(
            $value,
            $propertyAccessor->getValue($this->entity, $property)
        );
    }

    /**
     * @return array
     */
    public function entityDataProvider()
    {
        return [
            'entityClass'       => ['entityClass', '\stdClass'],
            'empty_entityClass' => ['entityClass', null],
            'entityId'          => ['entityId', 1],
            'empty_entityId'    => ['entityId', null],
            'changedFields'     => ['changedFields', ['field']],
        ];
    }

    /**
     * @param array $fields
     *
     * @dataProvider constructDataProvider
     */
    public function testConstruct(array $fields)
    {
        $fieldsChanges = new FieldsChanges($fields);
        $this->assertEquals($fields, $fieldsChanges->getChangedFields());
    }

    /**
     * @return array
     */
    public function constructDataProvider()
    {
        return [
            [[]],
            [['field']],
        ];
    }
}
