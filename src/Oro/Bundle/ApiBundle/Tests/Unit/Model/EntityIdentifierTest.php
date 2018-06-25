<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;

class EntityIdentifierTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorWithoutArguments()
    {
        $entityIdentifier = new EntityIdentifier();
        self::assertNull($entityIdentifier->getId());
        self::assertNull($entityIdentifier->getClass());
        self::assertSame([], $entityIdentifier->getAttributes());
    }

    public function testConstructor()
    {
        $entityIdentifier = new EntityIdentifier('id', 'class');
        self::assertEquals('id', $entityIdentifier->getId());
        self::assertEquals('class', $entityIdentifier->getClass());
    }

    public function testId()
    {
        $entityIdentifier = new EntityIdentifier();
        self::assertNull($entityIdentifier->getId());

        $entityIdentifier->setId('test');
        self::assertEquals('test', $entityIdentifier->getId());
    }

    public function testClass()
    {
        $entityIdentifier = new EntityIdentifier();
        self::assertNull($entityIdentifier->getClass());

        $entityIdentifier->setClass('test');
        self::assertEquals('test', $entityIdentifier->getClass());
    }

    public function testAttributes()
    {
        $entityIdentifier = new EntityIdentifier();

        $entityIdentifier->setAttribute('name', 'value');
        self::assertTrue($entityIdentifier->hasAttribute('name'));
        self::assertEquals('value', $entityIdentifier->getAttribute('name'));
        self::assertEquals(['name' => 'value'], $entityIdentifier->getAttributes());

        $entityIdentifier->removeAttribute('name');
        self::assertFalse($entityIdentifier->hasAttribute('name'));
        self::assertSame([], $entityIdentifier->getAttributes());
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
        self::assertTrue(isset($entityIdentifier['name']));
        self::assertEquals('value', $entityIdentifier['name']);
        self::assertEquals(['name' => 'value'], $entityIdentifier->getAttributes());

        unset($entityIdentifier['name']);
        self::assertFalse(isset($entityIdentifier['name']));
        self::assertSame([], $entityIdentifier->getAttributes());
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
