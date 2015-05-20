<?php

namespace Oro\Component\PropertyAccess\Tests\Unit;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\TestClass;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\TestClassMagicCall;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\TestClassMagicGet;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\Ticket5775Object;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
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
     * @expectedException \Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException
     */
    public function testGetValueThrowsExceptionForInvalidPropertyPathType()
    {
        $this->propertyAccessor->getValue(new \stdClass(), 123);
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testGetValueThrowsExceptionIfPropertyNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor->getValue($objectOrArray, $path);
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testGetValueThrowsExceptionIfIndexNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor = new PropertyAccessor();
        $this->propertyAccessor->getValue($objectOrArray, $path);
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
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
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
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
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "string" while trying to traverse path "foobar" at property "foobar".
     */
    // @codingStandardsIgnoreEnd
    public function testGetValueThrowsExceptionIfNotObjectOrArray()
    {
        $this->propertyAccessor->getValue('baz', 'foobar');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "NULL" while trying to traverse path "foobar" at property "foobar".
     */
    // @codingStandardsIgnoreEnd
    public function testGetValueThrowsExceptionIfNull()
    {
        $this->propertyAccessor->getValue(null, 'foobar');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "string" while trying to traverse path "foobar" at property "foobar".
     */
    // @codingStandardsIgnoreEnd
    public function testGetValueThrowsExceptionIfEmpty()
    {
        $this->propertyAccessor->getValue('', 'foobar');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
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
     * @expectedException \Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException
     */
    public function testSetValueThrowsExceptionForInvalidPropertyPathType()
    {
        $this->propertyAccessor->setValue(new \stdClass(), 123, 'Updated');
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
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
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testSetValueThrowsExceptionIfNotArrayAccess()
    {
        $this->propertyAccessor->setValue(new \stdClass(), 'index', 'Updated');
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException
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
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testSetValueThrowsExceptionIfThereAreMissingParameters()
    {
        $this->propertyAccessor->setValue(new TestClass('John'), 'publicAccessorWithMoreRequiredParameters', 'Updated');
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
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
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
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
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
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
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
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
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "NULL" while trying to traverse path "foobar.baz" at property "baz".
     */
    // @codingStandardsIgnoreEnd
    public function testSetValueNestedExceptionMessage()
    {
        $value = (object)array('foobar' => null);

        $this->propertyAccessor->setValue($value, 'foobar.baz', 'bam');
    }

    /**
     * @dataProvider getValidPropertyPathsForRemove
     */
    public function testRemove($objectOrArray, $path)
    {
        $this->propertyAccessor->remove($objectOrArray, $path);

        if (count(func_get_args()) === 3) {
            $expectedValue = func_get_args()[2];
            $actualValue   = $this->propertyAccessor->getValue($objectOrArray, $path);
            $this->assertSame($expectedValue, $actualValue);
        } else {
            try {
                $this->propertyAccessor->getValue($objectOrArray, $path);
                $this->fail(sprintf('It is expected that "%s" is removed.', $path));
            } catch (NoSuchPropertyException $ex) {
            }
        }
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException
     */
    public function testRemoveThrowsExceptionForInvalidPropertyPathType()
    {
        $this->propertyAccessor->remove(new \stdClass(), 123);
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testRemoveThrowsExceptionIfPropertyNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor->remove($objectOrArray, $path);
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testRemoveThrowsNoExceptionIfIndexNotFound($objectOrArray, $path)
    {
        $clone = unserialize(serialize($objectOrArray));

        $this->propertyAccessor->remove($objectOrArray, $path);

        try {
            $this->propertyAccessor->getValue($objectOrArray, $path);
            $this->fail(sprintf('It is expected that "%s" is removed.', $path));
        } catch (NoSuchPropertyException $ex) {
        }

        $this->assertEquals($clone, $objectOrArray);
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testRemoveThrowsExceptionIfNotArrayAccess()
    {
        $this->propertyAccessor->remove(new \stdClass(), 'index');
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testRemoveThrowsExceptionIfThereIsInvalidItemInGraph()
    {
        $objectOrArray = new \stdClass();
        $objectOrArray->root = ['index' => 123];

        $this->propertyAccessor->remove($objectOrArray, 'root.index.firstName');
    }

    public function testRemoveUpdatesMagicUnset()
    {
        $author = new TestClassMagicGet('John');

        $this->propertyAccessor->remove($author, 'magicProperty');

        $this->assertNull($author->__get('magicProperty'));
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testRemoveThrowsExceptionIfThereAreMissingParameters()
    {
        $this->propertyAccessor->remove(new TestClass('John'), 'publicAccessorWithMoreRequiredParameters');
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testRemoveThrowsExceptionIfPublicPropertyHasNoUnsetter()
    {
        $this->propertyAccessor->remove(new TestClass('John'), 'publicProperty');
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testRemoveThrowsExceptionIfProtectedProperty()
    {
        $this->propertyAccessor->remove(new TestClass('John'), 'protectedProperty');
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testRemoveThrowsExceptionIfPrivateProperty()
    {
        $this->propertyAccessor->remove(new TestClass('John'), 'privateProperty');
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testRemoveDoesNotUpdateMagicCallByDefault()
    {
        $author = new TestClassMagicCall('John');

        $this->propertyAccessor->remove($author, 'magicCallProperty');
    }

    public function testRemoveUpdatesMagicCallIfEnabled()
    {
        $this->propertyAccessor = new PropertyAccessor(true);

        $author = new TestClassMagicCall('John');

        $this->propertyAccessor->remove($author, 'magicCallProperty');

        $this->assertNull($author->__call('getMagicCallProperty', array()));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "string" while trying to traverse path "foobar" at property "foobar".
     */
    // @codingStandardsIgnoreEnd
    public function testRemoveThrowsExceptionIfNotObjectOrArray()
    {
        $value = 'baz';

        $this->propertyAccessor->remove($value, 'foobar');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "NULL" while trying to traverse path "foobar" at property "foobar".
     */
    // @codingStandardsIgnoreEnd
    public function testRemoveThrowsExceptionIfNull()
    {
        $value = null;

        $this->propertyAccessor->remove($value, 'foobar');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "string" while trying to traverse path "foobar" at property "foobar".
     */
    // @codingStandardsIgnoreEnd
    public function testRemoveThrowsExceptionIfEmpty()
    {
        $value = '';

        $this->propertyAccessor->remove($value, 'foobar');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on, but it found type "NULL" while trying to traverse path "foobar.baz" at property "baz".
     */
    // @codingStandardsIgnoreEnd
    public function testRemoveNestedExceptionMessage()
    {
        $value = (object)array('foobar' => null);

        $this->propertyAccessor->remove($value, 'foobar.baz');
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
            array(array('firstName' => 'John'), '[firstName]', 'John'),
            array(array('index' => array('firstName' => 'John')), 'index.firstName', 'John'),
            array(array('index' => array('firstName' => 'John')), '[index][firstName]', 'John'),
            array(array('index' => array('firstName' => 'John')), '[index].firstName', 'John'),
            array(array('index' => array('firstName' => 'John')), 'index[firstName]', 'John'),
            array((object)array('firstName' => 'John'), 'firstName', 'John'),
            array((object)array('firstName' => 'John'), '[firstName]', 'John'),
            array((object)array('property' => array('firstName' => 'John')), 'property.firstName', 'John'),
            array((object)array('property' => array('firstName' => 'John')), '[property][firstName]', 'John'),
            array((object)array('property' => array('firstName' => 'John')), '[property].firstName', 'John'),
            array((object)array('property' => array('firstName' => 'John')), 'property[firstName]', 'John'),
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
            array(array('%!@$§.' => 'John'), '[%!@$§.]', 'John'),
            array(array('index' => array('%!@$§.' => 'John')), 'index[%!@$§.]', 'John'),
            array((object)array('%!@$§' => 'John'), '%!@$§', 'John'),
        );
    }

    public function getValidPropertyPathsForRemove()
    {
        return array(
            array(array('John', 'Doo'), '0'),
            array(array('John', 'Doo'), '1'),
            array(array('firstName' => 'John'), 'firstName'),
            array(array('firstName' => 'John'), '[firstName]'),
            array(array('index' => array('firstName' => 'John')), 'index.firstName'),
            array(array('index' => array('firstName' => 'John')), '[index][firstName]'),
            array(array('index' => array('firstName' => 'John')), '[index].firstName'),
            array(array('index' => array('firstName' => 'John')), 'index[firstName]'),
            // Accessor methods
            array(new TestClass('John'), 'publicAccessor', null),
            array(new TestClass('John'), '[publicAccessor]', null),
            array(new TestClass('John'), 'publicAccessorWithDefaultValue', null),
            array(new TestClass('John'), '[publicAccessorWithDefaultValue]', null),
            // Methods are camelized
            array(new TestClass('John'), 'public_accessor', null),
            array(new TestClass('John'), '[public_accessor]', null),
            array(new TestClass('John'), '_public_accessor', null),
            array(new TestClass('John'), '[_public_accessor]', null),
            // Special chars
            array(array('%!@$§' => 'John'), '%!@$§'),
            array(array('%!@$§.' => 'John'), '[%!@$§.]'),
            array(array('index' => array('%!@$§.' => 'John')), 'index[%!@$§.]'),
        );
    }

    public function testTicket5755()
    {
        $object = new Ticket5775Object();

        $this->propertyAccessor->setValue($object, 'property', 'foobar');

        $this->assertEquals('foobar', $object->getProperty());
    }
}
