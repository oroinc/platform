<?php

namespace Oro\Component\PropertyAccess\Tests\Unit;

use Oro\Component\PropertyAccess\PropertyPath;

class PropertyPathTest extends \PHPUnit_Framework_TestCase
{
    public function testToString()
    {
        $path = 'reference.traversable[index].property';

        $propertyPath = new PropertyPath($path);

        $this->assertSame($path, (string)$propertyPath);
    }

    /**
     * @dataProvider validPathDataProvider
     */
    public function testValidPath($path, $elements)
    {
        $propertyPath = new PropertyPath($path);
        $this->assertSame($elements, $propertyPath->getElements());
    }

    public function validPathDataProvider()
    {
        return [
            ['[index]', ['index']],
            ['[0]', ['0']],
            ['[1]', ['1']],
            ['0', ['0']],
            ['1', ['1']],
            ['property', ['property']],
            ['parent.child', ['parent', 'child']],
            ['parent[child]', ['parent', 'child']],
            ['[parent].child', ['parent', 'child']],
            ['[parent][child]', ['parent', 'child']],
            ['grandpa.parent.child', ['grandpa', 'parent', 'child']],
            ['grandpa.parent[child]', ['grandpa', 'parent', 'child']],
            ['grandpa.parent[child.group]', ['grandpa', 'parent', 'child.group']],
            ['grandpa[parent].child', ['grandpa', 'parent', 'child']],
            ['grandpa[parent.group].child', ['grandpa', 'parent.group', 'child']],
        ];
    }

    /**
     * @dataProvider invalidPathDataProvider
     */
    public function testUnexpectedCharacters($path, $invalidToken, $errorPos)
    {
        $this->setExpectedException(
            '\Oro\Component\PropertyAccess\Exception\InvalidPropertyPathException',
            sprintf(
                'Could not parse property path "%s". Unexpected token "%s" at position %d.',
                $path,
                $invalidToken,
                $errorPos
            )
        );

        new PropertyPath($path);
    }

    public function invalidPathDataProvider()
    {
        return [
            ['property.', '.', 8],
            ['property.[', '.', 8],
            ['property..', '.', 8],
            ['property[', '[', 8],
            ['property[[', '[', 8],
            ['property[.', '[', 8],
            ['property[]', '[', 8],
            ['.property', '.', 0],
            ['property.[index]', '.', 8],
            ['[index]property', 'p', 7],
        ];
    }

    /**
     * @expectedException \Oro\Component\PropertyAccess\Exception\InvalidPropertyPathException
     * @expectedExceptionMessage The property path must not be empty.
     */
    public function testPathCannotBeEmpty()
    {
        new PropertyPath('');
    }

    /**
     * @expectedException \Oro\Component\PropertyAccess\Exception\InvalidPropertyPathException
     * @expectedExceptionMessage Expected argument of type "string", "NULL" given.
     */
    public function testPathCannotBeNull()
    {
        new PropertyPath(null);
    }

    /**
     * @expectedException \Oro\Component\PropertyAccess\Exception\InvalidPropertyPathException
     * @expectedExceptionMessage Expected argument of type "string", "integer" given.
     */
    public function testPathShouldBeString()
    {
        new PropertyPath(123);
    }

    /**
     * @expectedException \Oro\Component\PropertyAccess\Exception\InvalidPropertyPathException
     * @expectedExceptionMessage Expected argument of type "string", "boolean" given.
     */
    public function testPathCannotBeFalse()
    {
        new PropertyPath(false);
    }
}
