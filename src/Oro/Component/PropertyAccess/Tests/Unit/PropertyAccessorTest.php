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
 * @SuppressWarnings(PHPMD.TooManyMethods)
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

    public function getPathsWithUnexpectedType()
    {
        return array(
            array('', 'foobar'),
            array('foo', 'foobar'),
            array(null, 'foobar'),
            array(123, 'foobar'),
            array((object) array('prop' => null), 'prop.foobar'),
            array((object) array('prop' => (object) array('subProp' => null)), 'prop.subProp.foobar'),
            array(array('index' => null), '[index][foobar]'),
            array(array('index' => array('subIndex' => null)), '[index][subIndex][foobar]'),
        );
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
     * @dataProvider getValidPropertyPathsWhenIndexExceptionsDisabled
     */
    public function testGetValueWhenIndexExceptionsDisabled($objectOrArray, $path, $value)
    {
        $this->propertyAccessor = new PropertyAccessor(false, true);
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
     */
    public function testGetValueThrowsNoExceptionIfIndexNotFoundAndIndexExceptionsDisabled($objectOrArray, $path)
    {
        // When exceptions are disabled, non-existing indices can be read. In this case, null is returned
        $this->propertyAccessor = new PropertyAccessor(false, true);
        $this->assertNull($this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testGetValueThrowsExceptionIfIndexNotFound($objectOrArray, $path)
    {
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

    /**
     * @dataProvider getPathsWithUnexpectedType
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on
     */
    public function testGetValueThrowsExceptionIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->propertyAccessor->getValue($objectOrArray, $path);
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
        $testObject = new \stdClass();

        $this->propertyAccessor->setValue($testObject, 123, 'Updated');
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
     * @dataProvider getPathsWithMissingIndex
     */
    public function testSetValueThrowsNoExceptionIfIndexNotFoundAndIndexExceptionsDisabled($objectOrArray, $path)
    {
        $this->propertyAccessor = new PropertyAccessor(false, true);
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testSetValueThrowsExceptionIfNotArrayAccess()
    {
        $testObject = new \stdClass();

        $this->propertyAccessor->setValue($testObject, 'index', 'Updated');
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
        $testObject = new TestClass('John');

        $this->propertyAccessor->setValue($testObject, 'publicAccessorWithMoreRequiredParameters', 'Updated');
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

    /**
     * @dataProvider getPathsWithUnexpectedType
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on
     */
    public function testSetValueThrowsExceptionIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'value');
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
        $testObject = new \stdClass();

        $this->propertyAccessor->remove($testObject, 123);
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
        $testObject = new \stdClass();

        $this->propertyAccessor->remove($testObject, 'index');
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
        $testObject = new TestClass('John');

        $this->propertyAccessor->remove($testObject, 'publicAccessorWithMoreRequiredParameters');
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testRemoveThrowsExceptionIfPublicPropertyHasNoUnsetter()
    {
        $testObject = new TestClass('John');

        $this->propertyAccessor->remove($testObject, 'publicProperty');
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testRemoveThrowsExceptionIfProtectedProperty()
    {
        $testObject = new TestClass('John');

        $this->propertyAccessor->remove($testObject, 'protectedProperty');
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testRemoveThrowsExceptionIfPrivateProperty()
    {
        $testObject = new TestClass('John');

        $this->propertyAccessor->remove($testObject, 'privateProperty');
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

    /**
     * @dataProvider getPathsWithUnexpectedType
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on
     */
    public function testRemoveThrowsExceptionIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->propertyAccessor->remove($objectOrArray, $path);
    }

    public function testGetValueWhenArrayValueIsNull()
    {
        $this->assertNull(
            $this->propertyAccessor->getValue(array('index' => array('nullable' => null)), 'index.nullable')
        );
    }

    public function testGetValueWhenArrayValueIsNullAndIndexExceptionsDisabled()
    {
        $this->propertyAccessor = new PropertyAccessor(false, true);
        $this->assertNull(
            $this->propertyAccessor->getValue(array('index' => array('nullable' => null)), 'index.nullable')
        );
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsReadable($objectOrArray, $path)
    {
        $this->assertTrue($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getValidPropertyPathsWhenIndexExceptionsDisabled
     */
    public function testIsReadableWhenIndexExceptionsDisabled($objectOrArray, $path)
    {
        $this->propertyAccessor = new PropertyAccessor(false, true);
        $this->assertTrue($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     */
    public function testIsReadableReturnsFalseIfPropertyNotFound($objectOrArray, $path)
    {
        $this->assertFalse($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testIsReadableReturnsFalseIfIndexNotFound($objectOrArray, $path)
    {
        // Non-existing indices cannot be read
        $this->assertFalse($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testIsReadableReturnsTrueIfIndexNotFoundAndIndexExceptionsDisabled($objectOrArray, $path)
    {
        // When exceptions are disabled, non-existing indices can be read.
        // In this case, null is returned by getValue method
        $this->propertyAccessor = new PropertyAccessor(false, true);
        $this->assertTrue($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    public function testIsReadableRecognizesMagicGet()
    {
        $this->assertTrue($this->propertyAccessor->isReadable(new TestClassMagicGet('John'), 'magicProperty'));
    }

    public function testIsReadableDoesNotRecognizeMagicCallByDefault()
    {
        $this->assertFalse($this->propertyAccessor->isReadable(new TestClassMagicCall('John'), 'magicCallProperty'));
    }

    public function testIsReadableRecognizesMagicCallIfEnabled()
    {
        $this->propertyAccessor = new PropertyAccessor(true);

        $this->assertTrue($this->propertyAccessor->isReadable(new TestClassMagicCall('John'), 'magicCallProperty'));
    }

    /**
     * @dataProvider getPathsWithUnexpectedType
     */
    public function testIsReadableReturnsFalseIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->assertFalse($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsWritable($objectOrArray, $path)
    {
        $this->assertTrue($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     */
    public function testIsWritableReturnsFalseIfPropertyNotFound($objectOrArray, $path)
    {
        $this->assertFalse($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testIsWritableReturnsTrueIfIndexNotFound($objectOrArray, $path)
    {
        // Non-existing indices can be written. Arrays are created on-demand.
        $this->assertTrue($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testIsWritableReturnsTrueIfIndexNotFoundAndIndexExceptionsDisabled($objectOrArray, $path)
    {
        // Non-existing indices can be written even if exceptions are enabled
        $this->propertyAccessor = new PropertyAccessor(false, true);
        $this->assertTrue($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    public function testIsWritableRecognizesMagicSet()
    {
        $this->assertTrue($this->propertyAccessor->isWritable(new TestClassMagicGet('John'), 'magicProperty'));
    }

    public function testIsWritableDoesNotRecognizeMagicCallByDefault()
    {
        $this->assertFalse($this->propertyAccessor->isWritable(new TestClassMagicCall('John'), 'magicCallProperty'));
    }

    public function testIsWritableRecognizesMagicCallIfEnabled()
    {
        $this->propertyAccessor = new PropertyAccessor(true);

        $this->assertTrue($this->propertyAccessor->isWritable(new TestClassMagicCall('John'), 'magicCallProperty'));
    }

    /**
     * @dataProvider getPathsWithUnexpectedType
     */
    public function testIsWritableReturnsFalseIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->assertFalse($this->propertyAccessor->isWritable($objectOrArray, $path));
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
            array(new TestClass('John'), 'publicGetSetter', 'John'),

            // Methods are camelized
            array(new TestClass('John'), 'public_accessor', 'John'),
            array(new TestClass('John'), '_public_accessor', 'John'),

            // Special chars
            array(array('%!@$§' => 'John'), '%!@$§', 'John'),
            array(array('%!@$§.' => 'John'), '[%!@$§.]', 'John'),
            array(array('index' => array('%!@$§.' => 'John')), 'index[%!@$§.]', 'John'),
            array((object)array('%!@$§' => 'John'), '%!@$§', 'John'),
            array((object) array('property' => (object) array('%!@$§' => 'John')), 'property.%!@$§', 'John'),

            // Nested objects and arrays
            array(array('foo' => new TestClass('bar')), '[foo].publicGetSetter', 'bar'),
            array(new TestClass(array('foo' => 'bar')), 'publicGetSetter[foo]', 'bar'),
            array(new TestClass(new TestClass('bar')), 'publicGetter.publicGetSetter', 'bar'),
            array(
                new TestClass(new TestClass(new TestClass('bar'))),
                'publicGetter.publicGetter.publicGetSetter', 'bar'
            ),
        );
    }

    public function getValidPropertyPathsWhenIndexExceptionsDisabled()
    {
        return array_merge(
            $this->getValidPropertyPaths(),
            array(
                // Missing indices
                array(array('index' => array()), '[index][firstName]', null),
                array(array('root' => array('index' => array())), '[root][index][firstName]', null),

                // Nested objects and arrays
                array(new TestClass(array('foo' => new TestClass('bar'))), 'publicGetter[foo].publicGetSetter', 'bar'),
                array(
                    new TestClass(array('foo' => array('baz' => new TestClass('bar')))),
                    'publicGetter[foo][baz].publicGetSetter', 'bar'
                ),
            )
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

    public function testSetValueDeepWithMagicGetter()
    {
        $obj = new TestClassMagicGet('foo');
        $obj->publicProperty = array('foo' => array('bar' => 'some_value'));
        $this->propertyAccessor->setValue($obj, 'publicProperty[foo][bar]', 'Updated');
        $this->assertSame('Updated', $obj->publicProperty['foo']['bar']);
    }
}
