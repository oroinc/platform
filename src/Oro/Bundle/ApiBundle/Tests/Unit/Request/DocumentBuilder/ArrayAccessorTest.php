<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\DocumentBuilder;

use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ArrayAccessor;
use PHPUnit\Framework\TestCase;

class ArrayAccessorTest extends TestCase
{
    private ArrayAccessor $arrayAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->arrayAccessor = new ArrayAccessor();
    }

    public function testGetClassName(): void
    {
        self::assertEquals(
            'Test\Class',
            $this->arrayAccessor->getClassName(['__class__' => 'Test\Class'])
        );
        self::assertNull(
            $this->arrayAccessor->getClassName([])
        );
    }

    public function testGetValue(): void
    {
        self::assertEquals(
            'val',
            $this->arrayAccessor->getValue(['name' => 'val'], 'name')
        );
    }

    public function testGetValueForMetadataProperty(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('The "__class__" property does not exist.');

        $this->arrayAccessor->getValue(['__class__' => 'Test\Class'], '__class__');
    }

    public function testGetValueForNotExistingProperty(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('The "name" property does not exist.');

        $this->arrayAccessor->getValue([], 'name');
    }

    public function testHasProperty(): void
    {
        self::assertTrue(
            $this->arrayAccessor->hasProperty(['name' => 'val'], 'name')
        );
    }

    public function testHasPropertyForPropertyWithNullValue(): void
    {
        self::assertTrue(
            $this->arrayAccessor->hasProperty(['name' => null], 'name')
        );
    }

    public function testHasPropertyForMetadataProperty(): void
    {
        self::assertFalse(
            $this->arrayAccessor->hasProperty(['__class__' => 'Test\Class'], '__class__')
        );
    }

    public function testHasPropertyForNotExistingProperty(): void
    {
        self::assertFalse(
            $this->arrayAccessor->hasProperty([], 'name')
        );
    }

    public function testToArray(): void
    {
        self::assertEquals(
            [
                'name' => 'val'
            ],
            $this->arrayAccessor->toArray(
                [
                    '__class__' => 'Test\Class',
                    'name'      => 'val'
                ]
            )
        );
    }
}
