<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;

class EntityIdentifierTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWithoutArguments()
    {
        $entityIdentifier = new EntityIdentifier();
        $this->assertNull($entityIdentifier->getId());
        $this->assertNull($entityIdentifier->getClass());
        $this->assertSame([], $entityIdentifier->getAttributes());
    }

    public function testConstructor()
    {
        $entityIdentifier = new EntityIdentifier('id', 'class');
        $this->assertEquals('id', $entityIdentifier->getId());
        $this->assertEquals('class', $entityIdentifier->getClass());
    }

    public function testId()
    {
        $entityIdentifier = new EntityIdentifier();
        $this->assertNull($entityIdentifier->getId());

        $entityIdentifier->setId('test');
        $this->assertEquals('test', $entityIdentifier->getId());
    }

    public function testClass()
    {
        $entityIdentifier = new EntityIdentifier();
        $this->assertNull($entityIdentifier->getClass());

        $entityIdentifier->setClass('test');
        $this->assertEquals('test', $entityIdentifier->getClass());
    }

    public function testAttributes()
    {
        $entityIdentifier = new EntityIdentifier();

        $entityIdentifier->setAttribute('name', 'value');
        $this->assertTrue($entityIdentifier->hasAttribute('name'));
        $this->assertEquals('value', $entityIdentifier->getAttribute('name'));
        $this->assertEquals(['name' => 'value'], $entityIdentifier->getAttributes());

        $entityIdentifier->removeAttribute('name');
        $this->assertFalse($entityIdentifier->hasAttribute('name'));
        $this->assertSame([], $entityIdentifier->getAttributes());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "name" attribute does not exist.
     */
    public function testGetUnknownAttribute()
    {
        $entityIdentifier = new EntityIdentifier();
        $entityIdentifier->getAttribute('name');
    }

    public function testArrayAccess()
    {
        $entityIdentifier = new EntityIdentifier();

        $entityIdentifier['name'] = 'value';
        $this->assertTrue(isset($entityIdentifier['name']));
        $this->assertEquals('value', $entityIdentifier['name']);
        $this->assertEquals(['name' => 'value'], $entityIdentifier->getAttributes());

        unset($entityIdentifier['name']);
        $this->assertFalse(isset($entityIdentifier['name']));
        $this->assertSame([], $entityIdentifier->getAttributes());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "name" attribute does not exist.
     */
    public function testArrayAccessGetForUnknownAttribute()
    {
        $entityIdentifier = new EntityIdentifier();
        $entityIdentifier['name'];
    }
}
