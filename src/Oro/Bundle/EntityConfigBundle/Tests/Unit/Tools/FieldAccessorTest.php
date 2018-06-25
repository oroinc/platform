<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\FieldAccessorTestEntity;
use Oro\Bundle\EntityConfigBundle\Tools\FieldAccessor;

class FieldAccessorTest extends \PHPUnit\Framework\TestCase
{
    public function testGetValue()
    {
        $entity = new FieldAccessorTestEntity();
        $entity
            ->setName('testName')
            ->setDefaultName('testDefaultName')
            ->setAnotherName('testAnotherName');

        $this->assertEquals('testName', FieldAccessor::getValue($entity, 'name'));
        $this->assertEquals('testDefaultName', FieldAccessor::getValue($entity, 'default_name'));
        $this->assertEquals('testAnotherName', FieldAccessor::getValue($entity, 'anotherName'));
    }

    public function testSetValue()
    {
        $entity = new FieldAccessorTestEntity();
        FieldAccessor::setValue($entity, 'name', 'testName');
        FieldAccessor::setValue($entity, 'default_name', 'testDefaultName');
        FieldAccessor::setValue($entity, 'anotherName', 'testAnotherName');

        $this->assertEquals('testName', $entity->getName());
        $this->assertEquals('testDefaultName', $entity->getDefaultName());
        $this->assertEquals('testAnotherName', $entity->getAnotherName());
    }

    public function testAddAndRemoveValue()
    {
        $entity = new FieldAccessorTestEntity();
        $this->assertCount(0, $entity->getRelatedEntities());
        $this->assertCount(0, $entity->getAnotherRelatedEntities());

        $relatedEntity1 = new DemoEntity(1);
        $relatedEntity2 = new DemoEntity(2);

        FieldAccessor::addValue($entity, 'related_entity', $relatedEntity1);
        FieldAccessor::addValue($entity, 'anotherRelatedEntity', $relatedEntity2);
        $this->assertCount(1, $entity->getRelatedEntities());
        $this->assertSame($relatedEntity1, $entity->getRelatedEntities()->first());
        $this->assertCount(1, $entity->getAnotherRelatedEntities());
        $this->assertSame($relatedEntity2, $entity->getAnotherRelatedEntities()->first());

        FieldAccessor::removeValue($entity, 'related_entity', $relatedEntity1);
        FieldAccessor::removeValue($entity, 'anotherRelatedEntity', $relatedEntity2);
        $this->assertCount(0, $entity->getRelatedEntities());
        $this->assertCount(0, $entity->getAnotherRelatedEntities());
    }

    public function testHasGetter()
    {
        $entity = new FieldAccessorTestEntity();

        $this->assertTrue(FieldAccessor::hasGetter($entity, 'name'));
        $this->assertTrue(FieldAccessor::hasGetter($entity, 'default_name'));
        $this->assertTrue(FieldAccessor::hasGetter($entity, 'anotherName'));
        $this->assertFalse(FieldAccessor::hasGetter($entity, 'unknown'));
    }
}
