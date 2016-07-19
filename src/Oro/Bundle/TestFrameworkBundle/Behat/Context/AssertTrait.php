<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use \PHPUnit_Framework_Assert as Assert;

/**
 * This trait allows to use PHPUnit assertions in Behat tests in the same way
 * as they are uses in unit and functional tests.
 */
trait AssertTrait
{
    /**
     * Asserts that an array has a specified key.
     *
     * @param mixed              $key
     * @param array|\ArrayAccess $array
     * @param string             $message
     */
    public static function assertArrayHasKey($key, $array, $message = '')
    {
        Assert::assertArrayHasKey($key, $array, $message);
    }

    /**
     * Asserts that an array has a specified subset.
     *
     * @param array|\ArrayAccess $subset
     * @param array|\ArrayAccess $array
     * @param bool               $strict Check for object identity
     * @param string             $message
     */
    public static function assertArraySubset($subset, $array, $strict = false, $message = '')
    {
        Assert::assertArraySubset($subset, $array, $strict, $message);
    }

    /**
     * Asserts that an array does not have a specified key.
     *
     * @param mixed              $key
     * @param array|\ArrayAccess $array
     * @param string             $message
     */
    public static function assertArrayNotHasKey($key, $array, $message = '')
    {
        Assert::assertArrayNotHasKey($key, $array, $message);
    }

    /**
     * Asserts that a haystack contains a needle.
     *
     * @param mixed  $needle
     * @param mixed  $haystack
     * @param string $message
     * @param bool   $ignoreCase
     * @param bool   $checkForObjectIdentity
     * @param bool   $checkForNonObjectIdentity
     */
    public static function assertContains(
        $needle,
        $haystack,
        $message = '',
        $ignoreCase = false,
        $checkForObjectIdentity = true,
        $checkForNonObjectIdentity = false
    ) {
        Assert::assertContains(
            $needle,
            $haystack,
            $message,
            $ignoreCase,
            $checkForObjectIdentity,
            $checkForNonObjectIdentity
        );
    }

    /**
     * Asserts that a haystack that is stored in a static attribute of a class
     * or an attribute of an object contains a needle.
     *
     * @param mixed  $needle
     * @param string $haystackAttributeName
     * @param mixed  $haystackClassOrObject
     * @param string $message
     * @param bool   $ignoreCase
     * @param bool   $checkForObjectIdentity
     * @param bool   $checkForNonObjectIdentity
     */
    public static function assertAttributeContains(
        $needle,
        $haystackAttributeName,
        $haystackClassOrObject,
        $message = '',
        $ignoreCase = false,
        $checkForObjectIdentity = true,
        $checkForNonObjectIdentity = false
    ) {
        Assert::assertAttributeContains(
            $needle,
            $haystackAttributeName,
            $haystackClassOrObject,
            $message,
            $ignoreCase,
            $checkForObjectIdentity,
            $checkForNonObjectIdentity
        );
    }

    /**
     * Asserts that a haystack does not contain a needle.
     *
     * @param mixed  $needle
     * @param mixed  $haystack
     * @param string $message
     * @param bool   $ignoreCase
     * @param bool   $checkForObjectIdentity
     * @param bool   $checkForNonObjectIdentity
     */
    public static function assertNotContains(
        $needle,
        $haystack,
        $message = '',
        $ignoreCase = false,
        $checkForObjectIdentity = true,
        $checkForNonObjectIdentity = false
    ) {
        Assert::assertNotContains(
            $needle,
            $haystack,
            $message,
            $ignoreCase,
            $checkForObjectIdentity,
            $checkForNonObjectIdentity
        );
    }

    /**
     * Asserts that a haystack that is stored in a static attribute of a class
     * or an attribute of an object does not contain a needle.
     *
     * @param mixed  $needle
     * @param string $haystackAttributeName
     * @param mixed  $haystackClassOrObject
     * @param string $message
     * @param bool   $ignoreCase
     * @param bool   $checkForObjectIdentity
     * @param bool   $checkForNonObjectIdentity
     */
    public static function assertAttributeNotContains(
        $needle,
        $haystackAttributeName,
        $haystackClassOrObject,
        $message = '',
        $ignoreCase = false,
        $checkForObjectIdentity = true,
        $checkForNonObjectIdentity = false
    ) {
        Assert::assertAttributeNotContains(
            $needle,
            $haystackAttributeName,
            $haystackClassOrObject,
            $message,
            $ignoreCase,
            $checkForObjectIdentity,
            $checkForNonObjectIdentity
        );
    }

    /**
     * Asserts that a haystack contains only values of a given type.
     *
     * @param string $type
     * @param mixed  $haystack
     * @param bool   $isNativeType
     * @param string $message
     */
    public static function assertContainsOnly($type, $haystack, $isNativeType = null, $message = '')
    {
        Assert::assertContainsOnly($type, $haystack, $isNativeType, $message);
    }

    /**
     * Asserts that a haystack contains only instances of a given classname
     *
     * @param string             $classname
     * @param array|\Traversable $haystack
     * @param string             $message
     */
    public static function assertContainsOnlyInstancesOf($classname, $haystack, $message = '')
    {
        Assert::assertContainsOnlyInstancesOf($classname, $haystack, $message);
    }

    /**
     * Asserts that a haystack that is stored in a static attribute of a class
     * or an attribute of an object contains only values of a given type.
     *
     * @param string $type
     * @param string $haystackAttributeName
     * @param mixed  $haystackClassOrObject
     * @param bool   $isNativeType
     * @param string $message
     */
    public static function assertAttributeContainsOnly(
        $type,
        $haystackAttributeName,
        $haystackClassOrObject,
        $isNativeType = null,
        $message = ''
    ) {
        Assert::assertAttributeContainsOnly(
            $type,
            $haystackAttributeName,
            $haystackClassOrObject,
            $isNativeType,
            $message
        );
    }

    /**
     * Asserts that a haystack does not contain only values of a given type.
     *
     * @param string $type
     * @param mixed  $haystack
     * @param bool   $isNativeType
     * @param string $message
     */
    public static function assertNotContainsOnly($type, $haystack, $isNativeType = null, $message = '')
    {
        Assert::assertNotContainsOnly($type, $haystack, $isNativeType, $message);
    }

    /**
     * Asserts that a haystack that is stored in a static attribute of a class
     * or an attribute of an object does not contain only values of a given
     * type.
     *
     * @param string $type
     * @param string $haystackAttributeName
     * @param mixed  $haystackClassOrObject
     * @param bool   $isNativeType
     * @param string $message
     */
    public static function assertAttributeNotContainsOnly(
        $type,
        $haystackAttributeName,
        $haystackClassOrObject,
        $isNativeType = null,
        $message = ''
    ) {
        Assert::assertAttributeNotContainsOnly(
            $type,
            $haystackAttributeName,
            $haystackClassOrObject,
            $isNativeType,
            $message
        );
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable.
     *
     * @param int    $expectedCount
     * @param mixed  $haystack
     * @param string $message
     */
    public static function assertCount($expectedCount, $haystack, $message = '')
    {
        Assert::assertCount($expectedCount, $haystack, $message);
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable
     * that is stored in an attribute.
     *
     * @param int    $expectedCount
     * @param string $haystackAttributeName
     * @param mixed  $haystackClassOrObject
     * @param string $message
     */
    public static function assertAttributeCount(
        $expectedCount,
        $haystackAttributeName,
        $haystackClassOrObject,
        $message = ''
    ) {
        Assert::assertAttributeCount($expectedCount, $haystackAttributeName, $haystackClassOrObject, $message);
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable.
     *
     * @param int    $expectedCount
     * @param mixed  $haystack
     * @param string $message
     */
    public static function assertNotCount($expectedCount, $haystack, $message = '')
    {
        Assert::assertNotCount($expectedCount, $haystack, $message);
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable
     * that is stored in an attribute.
     *
     * @param int    $expectedCount
     * @param string $haystackAttributeName
     * @param mixed  $haystackClassOrObject
     * @param string $message
     */
    public static function assertAttributeNotCount(
        $expectedCount,
        $haystackAttributeName,
        $haystackClassOrObject,
        $message = ''
    ) {
        Assert::assertAttributeNotCount($expectedCount, $haystackAttributeName, $haystackClassOrObject, $message);
    }

    /**
     * Asserts that two variables are equal.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     * @param float  $delta
     * @param int    $maxDepth
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     */
    public static function assertEquals(
        $expected,
        $actual,
        $message = '',
        $delta = 0.0,
        $maxDepth = 10,
        $canonicalize = false,
        $ignoreCase = false
    ) {
        Assert::assertEquals($expected, $actual, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
    }

    /**
     * Asserts that a variable is equal to an attribute of an object.
     *
     * @param mixed  $expected
     * @param string $actualAttributeName
     * @param string $actualClassOrObject
     * @param string $message
     * @param float  $delta
     * @param int    $maxDepth
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     */
    public static function assertAttributeEquals(
        $expected,
        $actualAttributeName,
        $actualClassOrObject,
        $message = '',
        $delta = 0.0,
        $maxDepth = 10,
        $canonicalize = false,
        $ignoreCase = false
    ) {
        Assert::assertAttributeEquals(
            $expected,
            $actualAttributeName,
            $actualClassOrObject,
            $message,
            $delta,
            $maxDepth,
            $canonicalize,
            $ignoreCase
        );
    }

    /**
     * Asserts that two variables are not equal.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     * @param float  $delta
     * @param int    $maxDepth
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     */
    public static function assertNotEquals(
        $expected,
        $actual,
        $message = '',
        $delta = 0.0,
        $maxDepth = 10,
        $canonicalize = false,
        $ignoreCase = false
    ) {
        Assert::assertNotEquals($expected, $actual, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
    }

    /**
     * Asserts that a variable is not equal to an attribute of an object.
     *
     * @param mixed  $expected
     * @param string $actualAttributeName
     * @param string $actualClassOrObject
     * @param string $message
     * @param float  $delta
     * @param int    $maxDepth
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     */
    public static function assertAttributeNotEquals(
        $expected,
        $actualAttributeName,
        $actualClassOrObject,
        $message = '',
        $delta = 0.0,
        $maxDepth = 10,
        $canonicalize = false,
        $ignoreCase = false
    ) {
        Assert::assertAttributeNotEquals(
            $expected,
            $actualAttributeName,
            $actualClassOrObject,
            $message,
            $delta,
            $maxDepth,
            $canonicalize,
            $ignoreCase
        );
    }

    /**
     * Asserts that a variable is empty.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertEmpty($actual, $message = '')
    {
        Assert::assertEmpty($actual, $message);
    }

    /**
     * Asserts that a static attribute of a class or an attribute of an object
     * is empty.
     *
     * @param string $haystackAttributeName
     * @param mixed  $haystackClassOrObject
     * @param string $message
     */
    public static function assertAttributeEmpty($haystackAttributeName, $haystackClassOrObject, $message = '')
    {
        Assert::assertAttributeEmpty($haystackAttributeName, $haystackClassOrObject, $message);
    }

    /**
     * Asserts that a variable is not empty.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNotEmpty($actual, $message = '')
    {
        Assert::assertNotEmpty($actual, $message);
    }

    /**
     * Asserts that a static attribute of a class or an attribute of an object
     * is not empty.
     *
     * @param string $haystackAttributeName
     * @param mixed  $haystackClassOrObject
     * @param string $message
     */
    public static function assertAttributeNotEmpty($haystackAttributeName, $haystackClassOrObject, $message = '')
    {
        Assert::assertAttributeNotEmpty($haystackAttributeName, $haystackClassOrObject, $message);
    }

    /**
     * Asserts that a value is greater than another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertGreaterThan($expected, $actual, $message = '')
    {
        Assert::assertGreaterThan($expected, $actual, $message);
    }

    /**
     * Asserts that an attribute is greater than another value.
     *
     * @param mixed  $expected
     * @param string $actualAttributeName
     * @param string $actualClassOrObject
     * @param string $message
     */
    public static function assertAttributeGreaterThan(
        $expected,
        $actualAttributeName,
        $actualClassOrObject,
        $message = ''
    ) {
        Assert::assertAttributeGreaterThan($expected, $actualAttributeName, $actualClassOrObject, $message);
    }

    /**
     * Asserts that a value is greater than or equal to another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertGreaterThanOrEqual($expected, $actual, $message = '')
    {
        Assert::assertGreaterThanOrEqual($expected, $actual, $message);
    }

    /**
     * Asserts that an attribute is greater than or equal to another value.
     *
     * @param mixed  $expected
     * @param string $actualAttributeName
     * @param string $actualClassOrObject
     * @param string $message
     */
    public static function assertAttributeGreaterThanOrEqual(
        $expected,
        $actualAttributeName,
        $actualClassOrObject,
        $message = ''
    ) {
        Assert::assertAttributeGreaterThanOrEqual($expected, $actualAttributeName, $actualClassOrObject, $message);
    }

    /**
     * Asserts that a value is smaller than another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertLessThan($expected, $actual, $message = '')
    {
        Assert::assertLessThan($expected, $actual, $message);
    }

    /**
     * Asserts that an attribute is smaller than another value.
     *
     * @param mixed  $expected
     * @param string $actualAttributeName
     * @param string $actualClassOrObject
     * @param string $message
     */
    public static function assertAttributeLessThan($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        Assert::assertAttributeLessThan($expected, $actualAttributeName, $actualClassOrObject, $message);
    }

    /**
     * Asserts that a value is smaller than or equal to another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertLessThanOrEqual($expected, $actual, $message = '')
    {
        Assert::assertLessThanOrEqual($expected, $actual, $message);
    }

    /**
     * Asserts that an attribute is smaller than or equal to another value.
     *
     * @param mixed  $expected
     * @param string $actualAttributeName
     * @param string $actualClassOrObject
     * @param string $message
     */
    public static function assertAttributeLessThanOrEqual(
        $expected,
        $actualAttributeName,
        $actualClassOrObject,
        $message = ''
    ) {
        Assert::assertAttributeLessThanOrEqual($expected, $actualAttributeName, $actualClassOrObject, $message);
    }

    /**
     * Asserts that the contents of one file is equal to the contents of another
     * file.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     */
    public static function assertFileEquals(
        $expected,
        $actual,
        $message = '',
        $canonicalize = false,
        $ignoreCase = false
    ) {
        Assert::assertFileEquals($expected, $actual, $message, $canonicalize, $ignoreCase);
    }

    /**
     * Asserts that the contents of one file is not equal to the contents of
     * another file.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     */
    public static function assertFileNotEquals(
        $expected,
        $actual,
        $message = '',
        $canonicalize = false,
        $ignoreCase = false
    ) {
        Assert::assertFileNotEquals($expected, $actual, $message, $canonicalize, $ignoreCase);
    }

    /**
     * Asserts that the contents of a string is equal
     * to the contents of a file.
     *
     * @param string $expectedFile
     * @param string $actualString
     * @param string $message
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     */
    public static function assertStringEqualsFile(
        $expectedFile,
        $actualString,
        $message = '',
        $canonicalize = false,
        $ignoreCase = false
    ) {
        Assert::assertStringEqualsFile($expectedFile, $actualString, $message, $canonicalize, $ignoreCase);
    }

    /**
     * Asserts that the contents of a string is not equal
     * to the contents of a file.
     *
     * @param string $expectedFile
     * @param string $actualString
     * @param string $message
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     */
    public static function assertStringNotEqualsFile(
        $expectedFile,
        $actualString,
        $message = '',
        $canonicalize = false,
        $ignoreCase = false
    ) {
        Assert::assertStringNotEqualsFile($expectedFile, $actualString, $message, $canonicalize, $ignoreCase);
    }

    /**
     * Asserts that a file exists.
     *
     * @param string $filename
     * @param string $message
     */
    public static function assertFileExists($filename, $message = '')
    {
        Assert::assertFileExists($filename, $message);
    }

    /**
     * Asserts that a file does not exist.
     *
     * @param string $filename
     * @param string $message
     */
    public static function assertFileNotExists($filename, $message = '')
    {
        Assert::assertFileNotExists($filename, $message);
    }

    /**
     * Asserts that a condition is true.
     *
     * @param bool   $condition
     * @param string $message
     */
    public static function assertTrue($condition, $message = '')
    {
        Assert::assertTrue($condition, $message);
    }

    /**
     * Asserts that a condition is not true.
     *
     * @param bool   $condition
     * @param string $message
     */
    public static function assertNotTrue($condition, $message = '')
    {
        Assert::assertNotTrue($condition, $message);
    }

    /**
     * Asserts that a condition is false.
     *
     * @param bool   $condition
     * @param string $message
     */
    public static function assertFalse($condition, $message = '')
    {
        Assert::assertFalse($condition, $message);
    }

    /**
     * Asserts that a condition is not false.
     *
     * @param bool   $condition
     * @param string $message
     */
    public static function assertNotFalse($condition, $message = '')
    {
        Assert::assertNotFalse($condition, $message);
    }

    /**
     * Asserts that a variable is not null.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNotNull($actual, $message = '')
    {
        Assert::assertNotNull($actual, $message);
    }

    /**
     * Asserts that a variable is null.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNull($actual, $message = '')
    {
        Assert::assertNull($actual, $message);
    }

    /**
     * Asserts that a class has a specified attribute.
     *
     * @param string $attributeName
     * @param string $className
     * @param string $message
     */
    public static function assertClassHasAttribute($attributeName, $className, $message = '')
    {
        Assert::assertClassHasAttribute($attributeName, $className, $message);
    }

    /**
     * Asserts that a class does not have a specified attribute.
     *
     * @param string $attributeName
     * @param string $className
     * @param string $message
     */
    public static function assertClassNotHasAttribute($attributeName, $className, $message = '')
    {
        Assert::assertClassNotHasAttribute($attributeName, $className, $message);
    }

    /**
     * Asserts that a class has a specified static attribute.
     *
     * @param string $attributeName
     * @param string $className
     * @param string $message
     */
    public static function assertClassHasStaticAttribute($attributeName, $className, $message = '')
    {
        Assert::assertClassHasStaticAttribute($attributeName, $className, $message);
    }

    /**
     * Asserts that a class does not have a specified static attribute.
     *
     * @param string $attributeName
     * @param string $className
     * @param string $message
     */
    public static function assertClassNotHasStaticAttribute($attributeName, $className, $message = '')
    {
        Assert::assertClassNotHasStaticAttribute($attributeName, $className, $message);
    }

    /**
     * Asserts that an object has a specified attribute.
     *
     * @param string $attributeName
     * @param object $object
     * @param string $message
     */
    public static function assertObjectHasAttribute($attributeName, $object, $message = '')
    {
        Assert::assertObjectHasAttribute($attributeName, $object, $message);
    }

    /**
     * Asserts that an object does not have a specified attribute.
     *
     * @param string $attributeName
     * @param object $object
     * @param string $message
     */
    public static function assertObjectNotHasAttribute($attributeName, $object, $message = '')
    {
        Assert::assertObjectNotHasAttribute($attributeName, $object, $message);
    }

    /**
     * Asserts that two variables have the same type and value.
     * Used on objects, it asserts that two variables reference
     * the same object.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertSame($expected, $actual, $message = '')
    {
        Assert::assertSame($expected, $actual, $message);
    }

    /**
     * Asserts that a variable and an attribute of an object have the same type
     * and value.
     *
     * @param mixed  $expected
     * @param string $actualAttributeName
     * @param object $actualClassOrObject
     * @param string $message
     */
    public static function assertAttributeSame($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        Assert::assertAttributeSame($expected, $actualAttributeName, $actualClassOrObject, $message);
    }

    /**
     * Asserts that two variables do not have the same type and value.
     * Used on objects, it asserts that two variables do not reference
     * the same object.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNotSame($expected, $actual, $message = '')
    {
        Assert::assertNotSame($expected, $actual, $message);
    }

    /**
     * Asserts that a variable and an attribute of an object do not have the
     * same type and value.
     *
     * @param mixed  $expected
     * @param string $actualAttributeName
     * @param object $actualClassOrObject
     * @param string $message
     */
    public static function assertAttributeNotSame($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        Assert::assertAttributeNotSame($expected, $actualAttributeName, $actualClassOrObject, $message);
    }

    /**
     * Asserts that a variable is of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertInstanceOf($expected, $actual, $message = '')
    {
        Assert::assertInstanceOf($expected, $actual, $message);
    }

    /**
     * Asserts that an attribute is of a given type.
     *
     * @param string $expected
     * @param string $attributeName
     * @param mixed  $classOrObject
     * @param string $message
     */
    public static function assertAttributeInstanceOf($expected, $attributeName, $classOrObject, $message = '')
    {
        Assert::assertAttributeInstanceOf($expected, $attributeName, $classOrObject, $message);
    }

    /**
     * Asserts that a variable is not of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNotInstanceOf($expected, $actual, $message = '')
    {
        Assert::assertNotInstanceOf($expected, $actual, $message);
    }

    /**
     * Asserts that an attribute is of a given type.
     *
     * @param string $expected
     * @param string $attributeName
     * @param mixed  $classOrObject
     * @param string $message
     */
    public static function assertAttributeNotInstanceOf($expected, $attributeName, $classOrObject, $message = '')
    {
        Assert::assertAttributeNotInstanceOf($expected, $attributeName, $classOrObject, $message);
    }

    /**
     * Asserts that a variable is of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertInternalType($expected, $actual, $message = '')
    {
        Assert::assertInternalType($expected, $actual, $message);
    }

    /**
     * Asserts that an attribute is of a given type.
     *
     * @param string $expected
     * @param string $attributeName
     * @param mixed  $classOrObject
     * @param string $message
     */
    public static function assertAttributeInternalType($expected, $attributeName, $classOrObject, $message = '')
    {
        Assert::assertAttributeInternalType($expected, $attributeName, $classOrObject, $message);
    }

    /**
     * Asserts that a variable is not of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNotInternalType($expected, $actual, $message = '')
    {
        Assert::assertNotInternalType($expected, $actual, $message);
    }

    /**
     * Asserts that an attribute is of a given type.
     *
     * @param string $expected
     * @param string $attributeName
     * @param mixed  $classOrObject
     * @param string $message
     */
    public static function assertAttributeNotInternalType($expected, $attributeName, $classOrObject, $message = '')
    {
        Assert::assertAttributeNotInternalType($expected, $attributeName, $classOrObject, $message);
    }

    /**
     * Asserts that a string matches a given regular expression.
     *
     * @param string $pattern
     * @param string $string
     * @param string $message
     */
    public static function assertRegExp($pattern, $string, $message = '')
    {
        Assert::assertRegExp($pattern, $string, $message);
    }

    /**
     * Asserts that a string does not match a given regular expression.
     *
     * @param string $pattern
     * @param string $string
     * @param string $message
     */
    public static function assertNotRegExp($pattern, $string, $message = '')
    {
        Assert::assertNotRegExp($pattern, $string, $message);
    }

    /**
     * Assert that the size of two arrays (or `Countable` or `Traversable` objects)
     * is the same.
     *
     * @param array|\Countable|\Traversable $expected
     * @param array|\Countable|\Traversable $actual
     * @param string                        $message
     */
    public static function assertSameSize($expected, $actual, $message = '')
    {
        Assert::assertSameSize($expected, $actual, $message);
    }

    /**
     * Assert that the size of two arrays (or `Countable` or `Traversable` objects)
     * is not the same.
     *
     * @param array|\Countable|\Traversable $expected
     * @param array|\Countable|\Traversable $actual
     * @param string                        $message
     */
    public static function assertNotSameSize($expected, $actual, $message = '')
    {
        Assert::assertNotSameSize($expected, $actual, $message);
    }

    /**
     * Asserts that a string matches a given format string.
     *
     * @param string $format
     * @param string $string
     * @param string $message
     */
    public static function assertStringMatchesFormat($format, $string, $message = '')
    {
        Assert::assertStringMatchesFormat($format, $string, $message);
    }

    /**
     * Asserts that a string does not match a given format string.
     *
     * @param string $format
     * @param string $string
     * @param string $message
     */
    public static function assertStringNotMatchesFormat($format, $string, $message = '')
    {
        Assert::assertStringNotMatchesFormat($format, $string, $message);
    }

    /**
     * Asserts that a string matches a given format file.
     *
     * @param string $formatFile
     * @param string $string
     * @param string $message
     */
    public static function assertStringMatchesFormatFile($formatFile, $string, $message = '')
    {
        Assert::assertStringMatchesFormatFile($formatFile, $string, $message);
    }

    /**
     * Asserts that a string does not match a given format string.
     *
     * @param string $formatFile
     * @param string $string
     * @param string $message
     */
    public static function assertStringNotMatchesFormatFile($formatFile, $string, $message = '')
    {
        Assert::assertStringNotMatchesFormatFile($formatFile, $string, $message);
    }

    /**
     * Asserts that a string starts with a given prefix.
     *
     * @param string $prefix
     * @param string $string
     * @param string $message
     */
    public static function assertStringStartsWith($prefix, $string, $message = '')
    {
        Assert::assertStringStartsWith($prefix, $string, $message);
    }

    /**
     * Asserts that a string starts not with a given prefix.
     *
     * @param string $prefix
     * @param string $string
     * @param string $message
     */
    public static function assertStringStartsNotWith($prefix, $string, $message = '')
    {
        Assert::assertStringStartsNotWith($prefix, $string, $message);
    }

    /**
     * Asserts that a string ends with a given suffix.
     *
     * @param string $suffix
     * @param string $string
     * @param string $message
     */
    public static function assertStringEndsWith($suffix, $string, $message = '')
    {
        Assert::assertStringEndsWith($suffix, $string, $message);
    }

    /**
     * Asserts that a string ends not with a given suffix.
     *
     * @param string $suffix
     * @param string $string
     * @param string $message
     */
    public static function assertStringEndsNotWith($suffix, $string, $message = '')
    {
        Assert::assertStringEndsNotWith($suffix, $string, $message);
    }

    /**
     * Asserts that two XML files are equal.
     *
     * @param string $expectedFile
     * @param string $actualFile
     * @param string $message
     */
    public static function assertXmlFileEqualsXmlFile($expectedFile, $actualFile, $message = '')
    {
        Assert::assertXmlFileEqualsXmlFile($expectedFile, $actualFile, $message);
    }

    /**
     * Asserts that two XML files are not equal.
     *
     * @param string $expectedFile
     * @param string $actualFile
     * @param string $message
     */
    public static function assertXmlFileNotEqualsXmlFile($expectedFile, $actualFile, $message = '')
    {
        Assert::assertXmlFileNotEqualsXmlFile($expectedFile, $actualFile, $message);
    }

    /**
     * Asserts that two XML documents are equal.
     *
     * @param string $expectedFile
     * @param string $actualXml
     * @param string $message
     */
    public static function assertXmlStringEqualsXmlFile($expectedFile, $actualXml, $message = '')
    {
        Assert::assertXmlStringEqualsXmlFile($expectedFile, $actualXml, $message);
    }

    /**
     * Asserts that two XML documents are not equal.
     *
     * @param string $expectedFile
     * @param string $actualXml
     * @param string $message
     */
    public static function assertXmlStringNotEqualsXmlFile($expectedFile, $actualXml, $message = '')
    {
        Assert::assertXmlStringNotEqualsXmlFile($expectedFile, $actualXml, $message);
    }

    /**
     * Asserts that two XML documents are equal.
     *
     * @param string $expectedXml
     * @param string $actualXml
     * @param string $message
     */
    public static function assertXmlStringEqualsXmlString($expectedXml, $actualXml, $message = '')
    {
        Assert::assertXmlStringEqualsXmlString($expectedXml, $actualXml, $message);
    }

    /**
     * Asserts that two XML documents are not equal.
     *
     * @param string $expectedXml
     * @param string $actualXml
     * @param string $message
     */
    public static function assertXmlStringNotEqualsXmlString($expectedXml, $actualXml, $message = '')
    {
        Assert::assertXmlStringNotEqualsXmlString($expectedXml, $actualXml, $message);
    }

    /**
     * Asserts that a hierarchy of DOMElements matches.
     *
     * @param \DOMElement $expectedElement
     * @param \DOMElement $actualElement
     * @param bool        $checkAttributes
     * @param string      $message
     */
    public static function assertEqualXMLStructure(
        \DOMElement $expectedElement,
        \DOMElement $actualElement,
        $checkAttributes = false,
        $message = ''
    ) {
        Assert::assertEqualXMLStructure($expectedElement, $actualElement, $checkAttributes, $message);
    }

    /**
     * Evaluates a PHPUnit_Framework_Constraint matcher object.
     *
     * @param mixed                         $value
     * @param \PHPUnit_Framework_Constraint $constraint
     * @param string                        $message
     */
    public static function assertThat($value, \PHPUnit_Framework_Constraint $constraint, $message = '')
    {
        Assert::assertThat($value, $constraint, $message);
    }

    /**
     * Asserts that a string is a valid JSON string.
     *
     * @param string $actualJson
     * @param string $message
     */
    public static function assertJson($actualJson, $message = '')
    {
        Assert::assertJson($actualJson, $message);
    }

    /**
     * Asserts that two given JSON encoded objects or arrays are equal.
     *
     * @param string $expectedJson
     * @param string $actualJson
     * @param string $message
     */
    public static function assertJsonStringEqualsJsonString($expectedJson, $actualJson, $message = '')
    {
        Assert::assertJsonStringEqualsJsonString($expectedJson, $actualJson, $message);
    }

    /**
     * Asserts that two given JSON encoded objects or arrays are not equal.
     *
     * @param string $expectedJson
     * @param string $actualJson
     * @param string $message
     */
    public static function assertJsonStringNotEqualsJsonString($expectedJson, $actualJson, $message = '')
    {
        Assert::assertJsonStringNotEqualsJsonString($expectedJson, $actualJson, $message);
    }

    /**
     * Asserts that the generated JSON encoded object and the content of the given file are equal.
     *
     * @param string $expectedFile
     * @param string $actualJson
     * @param string $message
     */
    public static function assertJsonStringEqualsJsonFile($expectedFile, $actualJson, $message = '')
    {
        Assert::assertJsonStringEqualsJsonFile($expectedFile, $actualJson, $message);
    }

    /**
     * Asserts that the generated JSON encoded object and the content of the given file are not equal.
     *
     * @param string $expectedFile
     * @param string $actualJson
     * @param string $message
     */
    public static function assertJsonStringNotEqualsJsonFile($expectedFile, $actualJson, $message = '')
    {
        Assert::assertJsonStringNotEqualsJsonFile($expectedFile, $actualJson, $message);
    }

    /**
     * Asserts that two JSON files are not equal.
     *
     * @param string $expectedFile
     * @param string $actualFile
     * @param string $message
     */
    public static function assertJsonFileNotEqualsJsonFile($expectedFile, $actualFile, $message = '')
    {
        Assert::assertJsonFileNotEqualsJsonFile($expectedFile, $actualFile, $message);
    }

    /**
     * Asserts that two JSON files are equal.
     *
     * @param string $expectedFile
     * @param string $actualFile
     * @param string $message
     */
    public static function assertJsonFileEqualsJsonFile($expectedFile, $actualFile, $message = '')
    {
        Assert::assertJsonFileEqualsJsonFile($expectedFile, $actualFile, $message);
    }

    /**
     * Mark the test as incomplete.
     *
     * @param string $message
     */
    public static function markTestIncomplete($message = '')
    {
        Assert::markTestIncomplete($message);
    }

    /**
     * Mark the test as skipped.
     *
     * @param string $message
     */
    public static function markTestSkipped($message = '')
    {
        Assert::markTestSkipped($message);
    }
}
