<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess;

use Oro\Component\ConfigExpression\PropertyAccess\PropertyAccessor;

abstract class PropertyAccessorArrayAccessTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    protected function setUp()
    {
        $this->propertyAccessor = new PropertyAccessor();
    }

    abstract protected function getContainer(array $array);

    public function getValidPropertyPaths()
    {
        return array(
            array($this->getContainer(array('firstName' => 'John')), 'firstName', 'John'),
            array(
                $this->getContainer(array('person' => $this->getContainer(array('firstName' => 'John')))),
                'person.firstName',
                'John'
            ),
        );
    }

    public function getPathsWithMissingIndex()
    {
        return array(
            array($this->getContainer(array('firstName' => 'John')), 'lastName'),
            array($this->getContainer(array()), 'index.lastName'),
            array($this->getContainer(array('index' => array())), 'index.lastName'),
            array($this->getContainer(array('index' => array('firstName' => 'John'))), 'index.lastName'),
        );
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testGetValue($collection, $path, $value)
    {
        $this->assertSame($value, $this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     */
    public function testGetValueThrowsExceptionIfIndexNotFound($collection, $path)
    {
        $this->propertyAccessor = new PropertyAccessor();
        $this->propertyAccessor->getValue($collection, $path);
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testSetValue($collection, $path)
    {
        $this->propertyAccessor->setValue($collection, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testSetValueThrowsNoExceptionIfIndexNotFound($collection, $path)
    {
        $this->propertyAccessor->setValue($collection, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($collection, $path));
    }
}
