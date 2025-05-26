<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use PHPUnit\Framework\TestCase;

class EntityIdentifierTest extends TestCase
{
    public function testConstructorWithoutArguments(): void
    {
        $entityIdentifier = new EntityIdentifier();
        self::assertNull($entityIdentifier->getId());
        self::assertNull($entityIdentifier->getClass());
        self::assertSame([], $entityIdentifier->getAttributes());
    }

    public function testConstructor(): void
    {
        $entityIdentifier = new EntityIdentifier('id', 'class');
        self::assertEquals('id', $entityIdentifier->getId());
        self::assertEquals('class', $entityIdentifier->getClass());
    }

    public function testId(): void
    {
        $entityIdentifier = new EntityIdentifier();
        self::assertNull($entityIdentifier->getId());

        $entityIdentifier->setId('test');
        self::assertEquals('test', $entityIdentifier->getId());
    }

    public function testClass(): void
    {
        $entityIdentifier = new EntityIdentifier();
        self::assertNull($entityIdentifier->getClass());

        $entityIdentifier->setClass('test');
        self::assertEquals('test', $entityIdentifier->getClass());
    }

    public function testAttributes(): void
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

    public function testGetUnknownAttribute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "name" attribute does not exist.');

        $entityIdentifier = new EntityIdentifier();
        $entityIdentifier->getAttribute('name');
    }

    public function testArrayAccess(): void
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

    public function testArrayAccessGetForUnknownAttribute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "name" attribute does not exist.');

        $entityIdentifier = new EntityIdentifier();
        $entityIdentifier['name'];
    }
}
