<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\IntegrationBundle\Entity\ChangeSet;

class ChangeSetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChangeSet
     */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new ChangeSet();
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
            'entityClass'         => ['entityClass', '\stdClass'],
            'empty_entityClass'   => ['entityClass', null],
            'entityId'            => ['entityId', 1],
            'empty_entityId'      => ['entityId', null],
            'localChanges'        => ['localChanges', ['field']],
            'empty_localChanges'  => ['localChanges', null],
            'remoteChanges'       => ['remoteChanges', ['field']],
            'empty_remoteChanges' => ['remoteChanges', null],
        ];
    }
}
