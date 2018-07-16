<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\DocumentBuilder;

use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ArrayAccessor;

class ArrayAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ArrayAccessor */
    private $arrayAccessor;

    protected function setUp()
    {
        $this->arrayAccessor = new ArrayAccessor();
    }

    public function testGetClassName()
    {
        self::assertEquals(
            'Test\Class',
            $this->arrayAccessor->getClassName(['__class__' => 'Test\Class'])
        );
        self::assertNull(
            $this->arrayAccessor->getClassName([])
        );
    }

    public function testGetValue()
    {
        self::assertEquals(
            'val',
            $this->arrayAccessor->getValue(['name' => 'val'], 'name')
        );
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage The "__class__" property does not exist.
     */
    public function testGetValueForMetadataProperty()
    {
        $this->arrayAccessor->getValue(['__class__' => 'Test\Class'], '__class__');
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage The "name" property does not exist.
     */
    public function testGetValueForNotExistingProperty()
    {
        $this->arrayAccessor->getValue([], 'name');
    }

    public function testHasProperty()
    {
        self::assertTrue(
            $this->arrayAccessor->hasProperty(['name' => 'val'], 'name')
        );
    }

    public function testHasPropertyForPropertyWithNullValue()
    {
        self::assertTrue(
            $this->arrayAccessor->hasProperty(['name' => null], 'name')
        );
    }

    public function testHasPropertyForMetadataProperty()
    {
        self::assertFalse(
            $this->arrayAccessor->hasProperty(['__class__' => 'Test\Class'], '__class__')
        );
    }

    public function testHasPropertyForNotExistingProperty()
    {
        self::assertFalse(
            $this->arrayAccessor->hasProperty([], 'name')
        );
    }

    public function testToArray()
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
