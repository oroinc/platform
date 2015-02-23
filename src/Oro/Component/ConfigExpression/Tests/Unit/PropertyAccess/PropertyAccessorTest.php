<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess;

use Oro\Component\ConfigExpression\PropertyAccess\PropertyAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess\Fixtures\TestClass;
use Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess\Fixtures\TestClassMagicCall;
use Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess\Fixtures\TestClassMagicGet;
use Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess\Fixtures\Ticket5775Object;

class PropertyAccessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    protected function setUp()
    {
        $this->propertyAccessor = new PropertyAccessor();
    }

    public function getPathsWithMissingProperty()
    {
        return array(
            array((object)array('firstName' => 'John'), 'lastName'),
            array((object)array('property' => (object)array('firstName' => 'John')), 'property.lastName'),
            array(array('index' => (object)array('firstName' => 'John')), 'index.lastName'),
            array(new TestClass('John'), 'protectedProperty'),
            array(new TestClass('John'), 'privateProperty'),
            array(new TestClass('John'), 'protectedAccessor'),
            array(new TestClass('John'), 'protectedIsAccessor'),
            array(new TestClass('John'), 'protectedHasAccessor'),
            array(new TestClass('John'), 'privateAccessor'),
            array(new TestClass('John'), 'privateIsAccessor'),
            array(new TestClass('John'), 'privateHasAccessor'),
            // Properties are not camelized
            array(new TestClass('John'), 'public_property'),
        );
    }

    public function getPathsWithMissingIndex()
    {
        return array(
            array(array('firstName' => 'John'), 'lastName'),
            array(array(), 'index.lastName'),
            array(array('index' => array()), 'index.lastName'),
            array(array('index' => array('firstName' => 'John')), 'index.lastName'),
            array((object)array('property' => array('firstName' => 'John')), 'property.lastName'),
        );
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testGetValue($objectOrArray, $path, $value)
    {
        $this->assertSame($value, $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     */
    public function testGetValueThrowsExceptionForInvalidPropertyPathType()
    {
        $this->propertyAccessor->getValue(new \stdClass(), 123);
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     */
    public function testGetValueThrowsExceptionIfPropertyNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor->getValue($objectOrArray, $path);
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     */
    public function testGetValueThrowsExceptionIfIndexNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor = new PropertyAccessor();
        $this->propertyAccessor->getValue($objectOrArray, $path);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     */
    public function testGetValueThrowsExceptionIfNotArrayAccess()
    {
        $this->propertyAccessor->getValue(new \stdClass(), 'index');
    }

    public function testGetValueReadsMagicGet()
    {
        $this->assertSame('John', $this->propertyAccessor->getValue(new TestClassMagicGet('John'), 'magicProperty'));
    }

    // https://github.com/symfony/symfony/pull/4450
    public function testGetValueReadsMagicGetThatReturnsConstant()
    {
        $this->assertSame(
            'constant value',
            $this->propertyAccessor->getValue(new TestClassMagicGet('John'), 'constantMagicProperty')
        );
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     */
    public function testGetValueDoesNotReadMagicCallByDefault()
    {
        $this->propertyAccessor->getValue(new TestClassMagicCall('John'), 'magicCallProperty');
    }

    public function testGetValueReadsMagicCallIfEnabled()
    {
        $this->propertyAccessor = new PropertyAccessor(true);

        $this->assertSame(
            'John',
            $this->propertyAccessor->getValue(new TestClassMagicCall('John'), 'magicCallProperty')
        );
    }

    // https://github.com/symfony/symfony/pull/4450
    public function testGetValueReadsMagicCallThatReturnsConstant()
    {
        $this->propertyAccessor = new PropertyAccessor(true);

        $this->assertSame(
            'constant value',
            $this->propertyAccessor->getValue(new TestClassMagicCall('John'), 'constantMagicCallProperty')
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "string" while trying to traverse path "foobar" at property "foobar".
     */
    // @codingStandardsIgnoreEnd
    public function testGetValueThrowsExceptionIfNotObjectOrArray()
    {
        $this->propertyAccessor->getValue('baz', 'foobar');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "NULL" while trying to traverse path "foobar" at property "foobar".
     */
    // @codingStandardsIgnoreEnd
    public function testGetValueThrowsExceptionIfNull()
    {
        $this->propertyAccessor->getValue(null, 'foobar');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "string" while trying to traverse path "foobar" at property "foobar".
     */
    // @codingStandardsIgnoreEnd
    public function testGetValueThrowsExceptionIfEmpty()
    {
        $this->propertyAccessor->getValue('', 'foobar');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "NULL" while trying to traverse path "foobar.baz" at property "baz".
     */
    // @codingStandardsIgnoreEnd
    public function testGetValueNestedExceptionMessage()
    {
        $this->propertyAccessor->getValue((object)array('foobar' => null), 'foobar.baz');
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testSetValue($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     */
    public function testSetValueThrowsExceptionForInvalidPropertyPathType()
    {
        $this->propertyAccessor->setValue(new \stdClass(), 123, 'Updated');
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     */
    public function testSetValueThrowsExceptionIfPropertyNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testSetValueThrowsNoExceptionIfIndexNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     */
    public function testSetValueThrowsExceptionIfNotArrayAccess()
    {
        $this->propertyAccessor->setValue(new \stdClass(), 'index', 'Updated');
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     */
    public function testSetValueThrowsExceptionIfThereIsInvalidItemInGraph()
    {
        $objectOrArray = new \stdClass();
        $objectOrArray->root = ['index' => 123];

        $this->propertyAccessor->setValue($objectOrArray, 'root.index.firstName.', 'Updated');
    }

    public function testSetValueUpdatesMagicSet()
    {
        $author = new TestClassMagicGet('John');

        $this->propertyAccessor->setValue($author, 'magicProperty', 'Updated');

        $this->assertEquals('Updated', $author->__get('magicProperty'));
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     */
    public function testSetValueThrowsExceptionIfThereAreMissingParameters()
    {
        $this->propertyAccessor->setValue(new TestClass('John'), 'publicAccessorWithMoreRequiredParameters', 'Updated');
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     */
    public function testSetValueDoesNotUpdateMagicCallByDefault()
    {
        $author = new TestClassMagicCall('John');

        $this->propertyAccessor->setValue($author, 'magicCallProperty', 'Updated');
    }

    public function testSetValueUpdatesMagicCallIfEnabled()
    {
        $this->propertyAccessor = new PropertyAccessor(true);

        $author = new TestClassMagicCall('John');

        $this->propertyAccessor->setValue($author, 'magicCallProperty', 'Updated');

        $this->assertEquals('Updated', $author->__call('getMagicCallProperty', array()));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "string" while trying to traverse path "foobar" at property "foobar".
     */
    // @codingStandardsIgnoreEnd
    public function testSetValueThrowsExceptionIfNotObjectOrArray()
    {
        $value = 'baz';

        $this->propertyAccessor->setValue($value, 'foobar', 'bam');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "NULL" while trying to traverse path "foobar" at property "foobar".
     */
    // @codingStandardsIgnoreEnd
    public function testSetValueThrowsExceptionIfNull()
    {
        $value = null;

        $this->propertyAccessor->setValue($value, 'foobar', 'bam');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "string" while trying to traverse path "foobar" at property "foobar".
     */
    // @codingStandardsIgnoreEnd
    public function testSetValueThrowsExceptionIfEmpty()
    {
        $value = '';

        $this->propertyAccessor->setValue($value, 'foobar', 'bam');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "NULL" while trying to traverse path "foobar.baz" at property "baz".
     */
    // @codingStandardsIgnoreEnd
    public function testSetValueNestedExceptionMessage()
    {
        $value = (object)array('foobar' => null);

        $this->propertyAccessor->setValue($value, 'foobar.baz', 'bam');
    }

    public function testGetValueWhenArrayValueIsNull()
    {
        $this->propertyAccessor = new PropertyAccessor();
        $this->assertNull(
            $this->propertyAccessor->getValue(array('index' => array('nullable' => null)), 'index.nullable')
        );
    }

    public function getValidPropertyPaths()
    {
        return array(
            array(array('John', 'Doo'), '0', 'John'),
            array(array('John', 'Doo'), '1', 'Doo'),
            array(array('firstName' => 'John'), 'firstName', 'John'),
            array(array('index' => array('firstName' => 'John')), 'index.firstName', 'John'),
            array((object)array('firstName' => 'John'), 'firstName', 'John'),
            array((object)array('property' => array('firstName' => 'John')), 'property.firstName', 'John'),
            array(array('index' => (object)array('firstName' => 'John')), 'index.firstName', 'John'),
            array((object)array('property' => (object)array('firstName' => 'John')), 'property.firstName', 'John'),
            // Accessor methods
            array(new TestClass('John'), 'publicProperty', 'John'),
            array(new TestClass('John'), 'publicAccessor', 'John'),
            array(new TestClass('John'), 'publicAccessorWithDefaultValue', 'John'),
            array(new TestClass('John'), 'publicAccessorWithRequiredAndDefaultValue', 'John'),
            array(new TestClass('John'), 'publicIsAccessor', 'John'),
            array(new TestClass('John'), 'publicHasAccessor', 'John'),
            // Methods are camelized
            array(new TestClass('John'), 'public_accessor', 'John'),
            array(new TestClass('John'), '_public_accessor', 'John'),
            // Special chars
            array(array('%!@$§' => 'John'), '%!@$§', 'John'),
            array(array('index' => array('%!@$§' => 'John')), 'index.%!@$§', 'John'),
            array((object)array('%!@$§' => 'John'), '%!@$§', 'John'),
            array((object)array('property' => (object)array('%!@$§' => 'John')), 'property.%!@$§', 'John'),
        );
    }

    public function testTicket5755()
    {
        $object = new Ticket5775Object();

        $this->propertyAccessor->setValue($object, 'property', 'foobar');

        $this->assertEquals('foobar', $object->getProperty());
    }
}
