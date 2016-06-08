<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\DocumentBuilder;

use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ArrayAccessor;

class ArrayAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ArrayAccessor */
    protected $arrayAccessor;

    protected function setUp()
    {
        $this->arrayAccessor = new ArrayAccessor();
    }

    public function testGetClassName()
    {
        $this->assertEquals(
            'Test\Class',
            $this->arrayAccessor->getClassName(['__class__' => 'Test\Class'])
        );
        $this->assertNull(
            $this->arrayAccessor->getClassName([])
        );
    }

    public function testGetValue()
    {
        $this->assertEquals(
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
        $this->assertTrue(
            $this->arrayAccessor->hasProperty(['name' => 'val'], 'name')
        );
    }

    public function testHasPropertyForPropertyWithNullValue()
    {
        $this->assertTrue(
            $this->arrayAccessor->hasProperty(['name' => null], 'name')
        );
    }

    public function testHasPropertyForMetadataProperty()
    {
        $this->assertFalse(
            $this->arrayAccessor->hasProperty(['__class__' => 'Test\Class'], '__class__')
        );
    }

    public function testHasPropertyForNotExistingProperty()
    {
        $this->assertFalse(
            $this->arrayAccessor->hasProperty([], 'name')
        );
    }

    public function testToArray()
    {
        $this->assertEquals(
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
