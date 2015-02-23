<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess;

use Oro\Component\ConfigExpression\PropertyAccess\PropertyPath;

class PropertyPathTest extends \PHPUnit_Framework_TestCase
{
    public function testValidPropertyPath()
    {
        $path = 'property.lastName';

        $propertyPath = new PropertyPath($path);

        $this->assertEquals($path, (string)$propertyPath);
        $this->assertSame(['property', 'lastName'], $propertyPath->getElements());
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "string", "integer" given.
     */
    public function testNotStringPropertyPath()
    {
        new PropertyPath(123);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage The property path must not be empty.
     */
    public function testEmptyPropertyPath()
    {
        new PropertyPath('');
    }
}
