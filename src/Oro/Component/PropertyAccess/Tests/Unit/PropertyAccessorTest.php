<?php

namespace Oro\Component\PropertyAccess\Tests\Unit;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\TestClass;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\TestClassMagicCall;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\TestClassMagicGet;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\Ticket5775Object;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PropertyAccessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    protected function setUp()
    {
        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * @return array
     */
    public function getPathsWithUnexpectedType()
    {
        return [
            ['', 'foobar'],
            ['foo', 'foobar'],
            [null, 'foobar'],
            [123, 'foobar'],
            [(object) ['prop' => null], 'prop.foobar'],
            [(object) ['prop' => (object) ['subProp' => null]], 'prop.subProp.foobar'],
            [['index' => null], '[index][foobar]'],
            [['index' => ['subIndex' => null]], '[index][subIndex][foobar]'],
        ];
    }

    /**
     * @return array
     */
    public function getPathsWithMissingProperty()
    {
        return [
            [(object)['firstName' => 'John'], 'lastName'],
            [(object)['property' => (object)['firstName' => 'John']], 'property.lastName'],
            [['index' => (object)['firstName' => 'John']], 'index.lastName'],
            [new TestClass('John'), 'protectedProperty'],
            [new TestClass('John'), 'privateProperty'],
            [new TestClass('John'), 'protectedAccessor'],
            [new TestClass('John'), 'protectedIsAccessor'],
            [new TestClass('John'), 'protectedHasAccessor'],
            [new TestClass('John'), 'privateAccessor'],
            [new TestClass('John'), 'privateIsAccessor'],
            [new TestClass('John'), 'privateHasAccessor'],
            // Properties are not camelized
            [new TestClass('John'), 'public_property'],
        ];
    }

    /**
     * @return array
     */
    public function getPathsWithMissingIndex()
    {
        return [
            [['firstName' => 'John'], 'lastName'],
            [[], 'index.lastName'],
            [['index' => []], 'index.lastName'],
            [['index' => ['firstName' => 'John']], 'index.lastName'],
            [(object)['property' => ['firstName' => 'John']], 'property.lastName'],
        ];
    }

    /**
     * @dataProvider getValidPropertyPaths
     * @param object|array $objectOrArray
     * @param string $path
     * @param mixed $value
     */
    public function testGetValue($objectOrArray, $path, $value)
    {
        $this->assertSame($value, $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @dataProvider getValidPropertyPathsWhenIndexExceptionsDisabled
     * @param object|array $objectOrArray
     * @param string $path
     * @param mixed $value
     */
    public function testGetValueWhenIndexExceptionsDisabled($objectOrArray, $path, $value)
    {
        $this->propertyAccessor = new PropertyAccessor(false, true);
        $this->assertSame($value, $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\InvalidArgumentException
     */
    public function testGetValueThrowsExceptionForInvalidPropertyPathType()
    {
        $this->propertyAccessor->getValue(new \stdClass(), 123);
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testGetValueThrowsExceptionIfPropertyNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor->getValue($objectOrArray, $path);
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     * @param object|array $objectOrArray
     * @param string $path
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
     * @param object|array $objectOrArray
     * @param string $path
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
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testGetValueThrowsExceptionIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->propertyAccessor->getValue($objectOrArray, $path);
    }

    public function testGetValueWithReadPropertyCache()
    {
        /** @var PropertyAccessor|\PHPUnit\Framework\MockObject\MockObject $propertyAccessor */
        $propertyAccessor = $this->getMockBuilder(PropertyAccessor::class)
            ->setMethods(['camelize'])
            ->getMock();
        $propertyAccessor->expects($this->once())
            ->method('camelize')
            ->willReturn('PublicProperty');

        $object = new TestClass('John');
        $path = 'publicProperty';
        $value = 'John';

        // Filling in a property readPropertyCache
        $this->assertSame($value, $propertyAccessor->getValue($object, $path));
        // Using property readPropertyCache
        $this->assertSame($value, $propertyAccessor->getValue($object, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testSetValue($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\InvalidArgumentException
     */
    public function testSetValueThrowsExceptionForInvalidPropertyPathType()
    {
        $testObject = new \stdClass();

        $this->propertyAccessor->setValue($testObject, 123, 'Updated');
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testSetValueThrowsExceptionIfPropertyNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testSetValueThrowsNoExceptionIfIndexNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     * @param object|array $objectOrArray
     * @param string $path
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

        $this->assertEquals('Updated', $author->__call('getMagicCallProperty', []));
    }

    /**
     * @dataProvider getPathsWithUnexpectedType
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testSetValueThrowsExceptionIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'value');
    }

    /**
     * @dataProvider getValidPropertyPathsForRemove
     * @param object|array $objectOrArray
     * @param string $path
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
     * @expectedException \Symfony\Component\PropertyAccess\Exception\InvalidArgumentException
     */
    public function testRemoveThrowsExceptionForInvalidPropertyPathType()
    {
        $testObject = new \stdClass();

        $this->propertyAccessor->remove($testObject, 123);
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testRemoveThrowsExceptionIfPropertyNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor->remove($objectOrArray, $path);
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     * @param object|array $objectOrArray
     * @param string $path
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

        $this->assertNull($author->__call('getMagicCallProperty', []));
    }

    /**
     * @dataProvider getPathsWithUnexpectedType
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testRemoveThrowsExceptionIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->propertyAccessor->remove($objectOrArray, $path);
    }

    public function testGetValueWhenArrayValueIsNull()
    {
        $this->assertNull(
            $this->propertyAccessor->getValue(['index' => ['nullable' => null]], 'index.nullable')
        );
    }

    public function testGetValueWhenArrayValueIsNullAndIndexExceptionsDisabled()
    {
        $this->propertyAccessor = new PropertyAccessor(false, true);
        $this->assertNull(
            $this->propertyAccessor->getValue(['index' => ['nullable' => null]], 'index.nullable')
        );
    }

    /**
     * @dataProvider getValidPropertyPaths
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testIsReadable($objectOrArray, $path)
    {
        $this->assertTrue($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getValidPropertyPathsWhenIndexExceptionsDisabled
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testIsReadableWhenIndexExceptionsDisabled($objectOrArray, $path)
    {
        $this->propertyAccessor = new PropertyAccessor(false, true);
        $this->assertTrue($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testIsReadableReturnsFalseIfPropertyNotFound($objectOrArray, $path)
    {
        $this->assertFalse($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testIsReadableReturnsFalseIfIndexNotFound($objectOrArray, $path)
    {
        // Non-existing indices cannot be read
        $this->assertFalse($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     * @param object|array $objectOrArray
     * @param string $path
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
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testIsReadableReturnsFalseIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->assertFalse($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testIsWritable($objectOrArray, $path)
    {
        $this->assertTrue($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testIsWritableReturnsFalseIfPropertyNotFound($objectOrArray, $path)
    {
        $this->assertFalse($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testIsWritableReturnsTrueIfIndexNotFound($objectOrArray, $path)
    {
        // Non-existing indices can be written. Arrays are created on-demand.
        $this->assertTrue($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     * @param object|array $objectOrArray
     * @param string $path
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
     * @param object|array $objectOrArray
     * @param string $path
     */
    public function testIsWritableReturnsFalseIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->assertFalse($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @return array
     */
    public function getValidPropertyPaths()
    {
        return [
            [['John', 'Doo'], '0', 'John'],
            [['John', 'Doo'], '1', 'Doo'],
            [['firstName' => 'John'], 'firstName', 'John'],
            [['firstName' => 'John'], '[firstName]', 'John'],
            [['index' => ['firstName' => 'John']], 'index.firstName', 'John'],
            [['index' => ['firstName' => 'John']], '[index][firstName]', 'John'],
            [['index' => ['firstName' => 'John']], '[index].firstName', 'John'],
            [['index' => ['firstName' => 'John']], 'index[firstName]', 'John'],
            [(object)['firstName' => 'John'], 'firstName', 'John'],
            [(object)['firstName' => 'John'], '[firstName]', 'John'],
            [(object)['property' => ['firstName' => 'John']], 'property.firstName', 'John'],
            [(object)['property' => ['firstName' => 'John']], '[property][firstName]', 'John'],
            [(object)['property' => ['firstName' => 'John']], '[property].firstName', 'John'],
            [(object)['property' => ['firstName' => 'John']], 'property[firstName]', 'John'],
            [['index' => (object)['firstName' => 'John']], 'index.firstName', 'John'],
            [(object)['property' => (object)['firstName' => 'John']], 'property.firstName', 'John'],

            // Accessor methods
            [new TestClass('John'), 'publicProperty', 'John'],
            [new TestClass('John'), 'publicAccessor', 'John'],
            [new TestClass('John'), 'publicAccessorWithDefaultValue', 'John'],
            [new TestClass('John'), 'publicAccessorWithRequiredAndDefaultValue', 'John'],
            [new TestClass('John'), 'publicIsAccessor', 'John'],
            [new TestClass('John'), 'publicHasAccessor', 'John'],
            [new TestClass('John'), 'publicGetSetter', 'John'],

            // Methods are camelized
            [new TestClass('John'), 'public_accessor', 'John'],
            [new TestClass('John'), '_public_accessor', 'John'],

            // Special chars
            [['%!@$§' => 'John'], '%!@$§', 'John'],
            [['%!@$§.' => 'John'], '[%!@$§.]', 'John'],
            [['index' => ['%!@$§.' => 'John']], 'index[%!@$§.]', 'John'],
            [(object)['%!@$§' => 'John'], '%!@$§', 'John'],
            [(object)['property' => (object)['%!@$§' => 'John']], 'property.%!@$§', 'John'],

            // Nested objects and arrays
            [['foo' => new TestClass('bar')], '[foo].publicGetSetter', 'bar'],
            [new TestClass(['foo' => 'bar']), 'publicGetSetter[foo]', 'bar'],
            [new TestClass(new TestClass('bar')), 'publicGetter.publicGetSetter', 'bar'],
            [
                new TestClass(new TestClass(new TestClass('bar'))),
                'publicGetter.publicGetter.publicGetSetter', 'bar'
            ],
        ];
    }

    /**
     * @return array
     */
    public function getValidPropertyPathsWhenIndexExceptionsDisabled()
    {
        return array_merge(
            $this->getValidPropertyPaths(),
            [
                // Missing indices
                [['index' => []], '[index][firstName]', null],
                [['root' => ['index' => []]], '[root][index][firstName]', null],

                // Nested objects and arrays
                [new TestClass(['foo' => new TestClass('bar')]), 'publicGetter[foo].publicGetSetter', 'bar'],
                [
                    new TestClass(['foo' => ['baz' => new TestClass('bar')]]),
                    'publicGetter[foo][baz].publicGetSetter', 'bar'
                ],
            ]
        );
    }

    /**
     * @return array
     */
    public function getValidPropertyPathsForRemove()
    {
        return [
            [['John', 'Doo'], '0'],
            [['John', 'Doo'], '1'],
            [['firstName' => 'John'], 'firstName'],
            [['firstName' => 'John'], '[firstName]'],
            [['index' => ['firstName' => 'John']], 'index.firstName'],
            [['index' => ['firstName' => 'John']], '[index][firstName]'],
            [['index' => ['firstName' => 'John']], '[index].firstName'],
            [['index' => ['firstName' => 'John']], 'index[firstName]'],
            // Accessor methods
            [new TestClass('John'), 'publicAccessor', null],
            [new TestClass('John'), '[publicAccessor]', null],
            [new TestClass('John'), 'publicAccessorWithDefaultValue', null],
            [new TestClass('John'), '[publicAccessorWithDefaultValue]', null],
            // Methods are camelized
            [new TestClass('John'), 'public_accessor', null],
            [new TestClass('John'), '[public_accessor]', null],
            [new TestClass('John'), '_public_accessor', null],
            [new TestClass('John'), '[_public_accessor]', null],
            // Special chars
            [['%!@$§' => 'John'], '%!@$§'],
            [['%!@$§.' => 'John'], '[%!@$§.]'],
            [['index' => ['%!@$§.' => 'John']], 'index[%!@$§.]'],
        ];
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
        $obj->publicProperty = ['foo' => ['bar' => 'some_value']];
        $this->propertyAccessor->setValue($obj, 'publicProperty[foo][bar]', 'Updated');
        $this->assertSame('Updated', $obj->publicProperty['foo']['bar']);
    }

    public function testSetValueWithWritePropertyCache()
    {
        /** @var PropertyAccessor|\PHPUnit\Framework\MockObject\MockObject $propertyAccessor */
        $propertyAccessor = $this->getMockBuilder(PropertyAccessor::class)
            ->setMethods(['camelize'])
            ->getMock();
        $propertyAccessor->expects($this->once())
            ->method('camelize')
            ->willReturn('PublicProperty');

        $object = new TestClass('John');
        $path = 'publicProperty';
        $value = 'John';

        // Filling in a property writePropertyCache
        $propertyAccessor->setValue($object, $path, $value);
        // Using property writePropertyCache
        $propertyAccessor->setValue($object, $path, $value);
    }
}
