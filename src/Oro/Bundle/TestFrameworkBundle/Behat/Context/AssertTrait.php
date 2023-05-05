<?php
// phpcs:ignoreFile - keep the original style to simplify maintenance
namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use PHPUnit\Framework\Assert as Assert;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\ArrayHasKey;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\ClassHasAttribute;
use PHPUnit\Framework\Constraint\ClassHasStaticAttribute;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\Count;
use PHPUnit\Framework\Constraint\DirectoryExists;
use PHPUnit\Framework\Constraint\FileExists;
use PHPUnit\Framework\Constraint\GreaterThan;
use PHPUnit\Framework\Constraint\IsAnything;
use PHPUnit\Framework\Constraint\IsEmpty;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\Constraint\IsEqualCanonicalizing;
use PHPUnit\Framework\Constraint\IsEqualIgnoringCase;
use PHPUnit\Framework\Constraint\IsEqualWithDelta;
use PHPUnit\Framework\Constraint\IsFalse;
use PHPUnit\Framework\Constraint\IsFinite;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\Constraint\IsInfinite;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use PHPUnit\Framework\Constraint\IsJson;
use PHPUnit\Framework\Constraint\IsNan;
use PHPUnit\Framework\Constraint\IsNull;
use PHPUnit\Framework\Constraint\IsReadable;
use PHPUnit\Framework\Constraint\IsTrue;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\Constraint\IsWritable;
use PHPUnit\Framework\Constraint\LessThan;
use PHPUnit\Framework\Constraint\LogicalAnd;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\Constraint\LogicalOr;
use PHPUnit\Framework\Constraint\LogicalXor;
use PHPUnit\Framework\Constraint\ObjectHasAttribute;
use PHPUnit\Framework\Constraint\RegularExpression;
use PHPUnit\Framework\Constraint\StringContains;
use PHPUnit\Framework\Constraint\StringEndsWith;
use PHPUnit\Framework\Constraint\StringMatchesFormatDescription;
use PHPUnit\Framework\Constraint\StringStartsWith;
use PHPUnit\Framework\Constraint\TraversableContainsEqual;
use PHPUnit\Framework\Constraint\TraversableContainsIdentical;
use PHPUnit\Framework\Constraint\TraversableContainsOnly;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\IncompleteTestError;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount as AnyInvokedCountMatcher;
use PHPUnit\Framework\MockObject\Rule\InvokedAtIndex as InvokedAtIndexMatcher;
use PHPUnit\Framework\MockObject\Rule\InvokedAtLeastCount as InvokedAtLeastCountMatcher;
use PHPUnit\Framework\MockObject\Rule\InvokedAtLeastOnce as InvokedAtLeastOnceMatcher;
use PHPUnit\Framework\MockObject\Rule\InvokedAtMostCount as InvokedAtMostCountMatcher;
use PHPUnit\Framework\MockObject\Rule\InvokedCount as InvokedCountMatcher;
use PHPUnit\Framework\MockObject\Stub\ConsecutiveCalls as ConsecutiveCallsStub;
use PHPUnit\Framework\MockObject\Stub\Exception as ExceptionStub;
use PHPUnit\Framework\MockObject\Stub\ReturnArgument as ReturnArgumentStub;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback as ReturnCallbackStub;
use PHPUnit\Framework\MockObject\Stub\ReturnSelf as ReturnSelfStub;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use PHPUnit\Framework\MockObject\Stub\ReturnValueMap as ReturnValueMapStub;
use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Framework\SyntheticSkippedError;

/**
 * This trait allows to use PHPUnit assertions in Behat tests.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
trait AssertTrait
{
    /**
     * Asserts that an array has a specified key.
     *
     * @param int|string         $key
     * @param array|\ArrayAccess $array
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertArrayHasKey
     */
    public static function assertArrayHasKey($key, $array, string $message = ''): void
    {
        Assert::assertArrayHasKey(...\func_get_args());
    }

    /**
     * Asserts that an array does not have a specified key.
     *
     * @param int|string         $key
     * @param array|\ArrayAccess $array
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertArrayNotHasKey
     */
    public static function assertArrayNotHasKey($key, $array, string $message = ''): void
    {
        Assert::assertArrayNotHasKey(...\func_get_args());
    }

    /**
     * Asserts that a haystack contains a needle.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertContains
     */
    public static function assertContains($needle, iterable $haystack, string $message = ''): void
    {
        Assert::assertContains(...\func_get_args());
    }

    public static function assertContainsEquals($needle, iterable $haystack, string $message = ''): void
    {
        Assert::assertContainsEquals(...\func_get_args());
    }

    /**
     * Asserts that a haystack does not contain a needle.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertNotContains
     */
    public static function assertNotContains($needle, iterable $haystack, string $message = ''): void
    {
        Assert::assertNotContains(...\func_get_args());
    }

    public static function assertNotContainsEquals($needle, iterable $haystack, string $message = ''): void
    {
        Assert::assertNotContainsEquals(...\func_get_args());
    }

    /**
     * Asserts that a haystack contains only values of a given type.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertContainsOnly
     */
    public static function assertContainsOnly(string $type, iterable $haystack, ?bool $isNativeType = null, string $message = ''): void
    {
        Assert::assertContainsOnly(...\func_get_args());
    }

    /**
     * Asserts that a haystack contains only instances of a given class name.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertContainsOnlyInstancesOf
     */
    public static function assertContainsOnlyInstancesOf(string $className, iterable $haystack, string $message = ''): void
    {
        Assert::assertContainsOnlyInstancesOf(...\func_get_args());
    }

    /**
     * Asserts that a haystack does not contain only values of a given type.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertNotContainsOnly
     */
    public static function assertNotContainsOnly(string $type, iterable $haystack, ?bool $isNativeType = null, string $message = ''): void
    {
        Assert::assertNotContainsOnly(...\func_get_args());
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable.
     *
     * @param \Countable|iterable $haystack
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertCount
     */
    public static function assertCount(int $expectedCount, $haystack, string $message = ''): void
    {
        Assert::assertCount(...\func_get_args());
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable.
     *
     * @param \Countable|iterable $haystack
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertNotCount
     */
    public static function assertNotCount(int $expectedCount, $haystack, string $message = ''): void
    {
        Assert::assertNotCount(...\func_get_args());
    }

    /**
     * Asserts that two variables are equal.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertEquals
     */
    public static function assertEquals($expected, $actual, string $message = ''): void
    {
        Assert::assertEquals(...\func_get_args());
    }

    /**
     * Asserts that two variables are equal (canonicalizing).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertEqualsCanonicalizing
     */
    public static function assertEqualsCanonicalizing($expected, $actual, string $message = ''): void
    {
        Assert::assertEqualsCanonicalizing(...\func_get_args());
    }

    /**
     * Asserts that two variables are equal (ignoring case).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertEqualsIgnoringCase
     */
    public static function assertEqualsIgnoringCase($expected, $actual, string $message = ''): void
    {
        Assert::assertEqualsIgnoringCase(...\func_get_args());
    }

    /**
     * Asserts that two variables are equal (with delta).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertEqualsWithDelta
     */
    public static function assertEqualsWithDelta($expected, $actual, float $delta, string $message = ''): void
    {
        Assert::assertEqualsWithDelta(...\func_get_args());
    }

    /**
     * Asserts that two variables are not equal.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertNotEquals
     */
    public static function assertNotEquals($expected, $actual, string $message = ''): void
    {
        Assert::assertNotEquals(...\func_get_args());
    }

    /**
     * Asserts that two variables are not equal (canonicalizing).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertNotEqualsCanonicalizing
     */
    public static function assertNotEqualsCanonicalizing($expected, $actual, string $message = ''): void
    {
        Assert::assertNotEqualsCanonicalizing(...\func_get_args());
    }

    /**
     * Asserts that two variables are not equal (ignoring case).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertNotEqualsIgnoringCase
     */
    public static function assertNotEqualsIgnoringCase($expected, $actual, string $message = ''): void
    {
        Assert::assertNotEqualsIgnoringCase(...\func_get_args());
    }

    /**
     * Asserts that two variables are not equal (with delta).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertNotEqualsWithDelta
     */
    public static function assertNotEqualsWithDelta($expected, $actual, float $delta, string $message = ''): void
    {
        Assert::assertNotEqualsWithDelta(...\func_get_args());
    }

    /**
     * Asserts that a variable is empty.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert empty $actual
     *
     * @see Assert::assertEmpty
     */
    public static function assertEmpty($actual, string $message = ''): void
    {
        Assert::assertEmpty(...\func_get_args());
    }

    /**
     * Asserts that a variable is not empty.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !empty $actual
     *
     * @see Assert::assertNotEmpty
     */
    public static function assertNotEmpty($actual, string $message = ''): void
    {
        Assert::assertNotEmpty(...\func_get_args());
    }

    /**
     * Asserts that a value is greater than another value.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertGreaterThan
     */
    public static function assertGreaterThan($expected, $actual, string $message = ''): void
    {
        Assert::assertGreaterThan(...\func_get_args());
    }

    /**
     * Asserts that a value is greater than or equal to another value.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertGreaterThanOrEqual
     */
    public static function assertGreaterThanOrEqual($expected, $actual, string $message = ''): void
    {
        Assert::assertGreaterThanOrEqual(...\func_get_args());
    }

    /**
     * Asserts that a value is smaller than another value.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertLessThan
     */
    public static function assertLessThan($expected, $actual, string $message = ''): void
    {
        Assert::assertLessThan(...\func_get_args());
    }

    /**
     * Asserts that a value is smaller than or equal to another value.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertLessThanOrEqual
     */
    public static function assertLessThanOrEqual($expected, $actual, string $message = ''): void
    {
        Assert::assertLessThanOrEqual(...\func_get_args());
    }

    /**
     * Asserts that the contents of one file is equal to the contents of another
     * file.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFileEquals
     */
    public static function assertFileEquals(string $expected, string $actual, string $message = ''): void
    {
        Assert::assertFileEquals(...\func_get_args());
    }

    /**
     * Asserts that the contents of one file is equal to the contents of another
     * file (canonicalizing).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFileEqualsCanonicalizing
     */
    public static function assertFileEqualsCanonicalizing(string $expected, string $actual, string $message = ''): void
    {
        Assert::assertFileEqualsCanonicalizing(...\func_get_args());
    }

    /**
     * Asserts that the contents of one file is equal to the contents of another
     * file (ignoring case).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFileEqualsIgnoringCase
     */
    public static function assertFileEqualsIgnoringCase(string $expected, string $actual, string $message = ''): void
    {
        Assert::assertFileEqualsIgnoringCase(...\func_get_args());
    }

    /**
     * Asserts that the contents of one file is not equal to the contents of
     * another file.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFileNotEquals
     */
    public static function assertFileNotEquals(string $expected, string $actual, string $message = ''): void
    {
        Assert::assertFileNotEquals(...\func_get_args());
    }

    /**
     * Asserts that the contents of one file is not equal to the contents of another
     * file (canonicalizing).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFileNotEqualsCanonicalizing
     */
    public static function assertFileNotEqualsCanonicalizing(string $expected, string $actual, string $message = ''): void
    {
        Assert::assertFileNotEqualsCanonicalizing(...\func_get_args());
    }

    /**
     * Asserts that the contents of one file is not equal to the contents of another
     * file (ignoring case).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFileNotEqualsIgnoringCase
     */
    public static function assertFileNotEqualsIgnoringCase(string $expected, string $actual, string $message = ''): void
    {
        Assert::assertFileNotEqualsIgnoringCase(...\func_get_args());
    }

    /**
     * Asserts that the contents of a string is equal
     * to the contents of a file.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringEqualsFile
     */
    public static function assertStringEqualsFile(string $expectedFile, string $actualString, string $message = ''): void
    {
        Assert::assertStringEqualsFile(...\func_get_args());
    }

    /**
     * Asserts that the contents of a string is equal
     * to the contents of a file (canonicalizing).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringEqualsFileCanonicalizing
     */
    public static function assertStringEqualsFileCanonicalizing(string $expectedFile, string $actualString, string $message = ''): void
    {
        Assert::assertStringEqualsFileCanonicalizing(...\func_get_args());
    }

    /**
     * Asserts that the contents of a string is equal
     * to the contents of a file (ignoring case).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringEqualsFileIgnoringCase
     */
    public static function assertStringEqualsFileIgnoringCase(string $expectedFile, string $actualString, string $message = ''): void
    {
        Assert::assertStringEqualsFileIgnoringCase(...\func_get_args());
    }

    /**
     * Asserts that the contents of a string is not equal
     * to the contents of a file.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringNotEqualsFile
     */
    public static function assertStringNotEqualsFile(string $expectedFile, string $actualString, string $message = ''): void
    {
        Assert::assertStringNotEqualsFile(...\func_get_args());
    }

    /**
     * Asserts that the contents of a string is not equal
     * to the contents of a file (canonicalizing).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringNotEqualsFileCanonicalizing
     */
    public static function assertStringNotEqualsFileCanonicalizing(string $expectedFile, string $actualString, string $message = ''): void
    {
        Assert::assertStringNotEqualsFileCanonicalizing(...\func_get_args());
    }

    /**
     * Asserts that the contents of a string is not equal
     * to the contents of a file (ignoring case).
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringNotEqualsFileIgnoringCase
     */
    public static function assertStringNotEqualsFileIgnoringCase(string $expectedFile, string $actualString, string $message = ''): void
    {
        Assert::assertStringNotEqualsFileIgnoringCase(...\func_get_args());
    }

    /**
     * Asserts that a file/dir is readable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertIsReadable
     */
    public static function assertIsReadable(string $filename, string $message = ''): void
    {
        Assert::assertIsReadable(...\func_get_args());
    }

    /**
     * Asserts that a file/dir exists and is not readable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertIsNotReadable
     */
    public static function assertIsNotReadable(string $filename, string $message = ''): void
    {
        Assert::assertIsNotReadable(...\func_get_args());
    }

    /**
     * Asserts that a file/dir exists and is writable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertIsWritable
     */
    public static function assertIsWritable(string $filename, string $message = ''): void
    {
        Assert::assertIsWritable(...\func_get_args());
    }

    /**
     * Asserts that a file/dir exists and is not writable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertIsNotWritable
     */
    public static function assertIsNotWritable(string $filename, string $message = ''): void
    {
        Assert::assertIsNotWritable(...\func_get_args());
    }

    /**
     * Asserts that a directory exists.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertDirectoryExists
     */
    public static function assertDirectoryExists(string $directory, string $message = ''): void
    {
        Assert::assertDirectoryExists(...\func_get_args());
    }

    /**
     * Asserts that a directory does not exist.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertDirectoryDoesNotExist
     */
    public static function assertDirectoryDoesNotExist(string $directory, string $message = ''): void
    {
        Assert::assertDirectoryDoesNotExist(...\func_get_args());
    }

    /**
     * Asserts that a directory exists and is readable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertDirectoryIsReadable
     */
    public static function assertDirectoryIsReadable(string $directory, string $message = ''): void
    {
        Assert::assertDirectoryIsReadable(...\func_get_args());
    }

    /**
     * Asserts that a directory exists and is not readable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertDirectoryIsNotReadable
     */
    public static function assertDirectoryIsNotReadable(string $directory, string $message = ''): void
    {
        Assert::assertDirectoryIsNotReadable(...\func_get_args());
    }

    /**
     * Asserts that a directory exists and is writable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertDirectoryIsWritable
     */
    public static function assertDirectoryIsWritable(string $directory, string $message = ''): void
    {
        Assert::assertDirectoryIsWritable(...\func_get_args());
    }

    /**
     * Asserts that a directory exists and is not writable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertDirectoryIsNotWritable
     */
    public static function assertDirectoryIsNotWritable(string $directory, string $message = ''): void
    {
        Assert::assertDirectoryIsNotWritable(...\func_get_args());
    }

    /**
     * Asserts that a file exists.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFileExists
     */
    public static function assertFileExists(string $filename, string $message = ''): void
    {
        Assert::assertFileExists(...\func_get_args());
    }

    /**
     * Asserts that a file does not exist.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFileDoesNotExist
     */
    public static function assertFileDoesNotExist(string $filename, string $message = ''): void
    {
        Assert::assertFileDoesNotExist(...\func_get_args());
    }

    /**
     * Asserts that a file exists and is readable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFileIsReadable
     */
    public static function assertFileIsReadable(string $file, string $message = ''): void
    {
        Assert::assertFileIsReadable(...\func_get_args());
    }

    /**
     * Asserts that a file exists and is not readable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFileIsNotReadable
     */
    public static function assertFileIsNotReadable(string $file, string $message = ''): void
    {
        Assert::assertFileIsNotReadable(...\func_get_args());
    }

    /**
     * Asserts that a file exists and is writable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFileIsWritable
     */
    public static function assertFileIsWritable(string $file, string $message = ''): void
    {
        Assert::assertFileIsWritable(...\func_get_args());
    }

    /**
     * Asserts that a file exists and is not writable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFileIsNotWritable
     */
    public static function assertFileIsNotWritable(string $file, string $message = ''): void
    {
        Assert::assertFileIsNotWritable(...\func_get_args());
    }

    /**
     * Asserts that a condition is true.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert true $condition
     *
     * @see Assert::assertTrue
     */
    public static function assertTrue($condition, string $message = ''): void
    {
        Assert::assertTrue(...\func_get_args());
    }

    /**
     * Asserts that a condition is not true.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !true $condition
     *
     * @see Assert::assertNotTrue
     */
    public static function assertNotTrue($condition, string $message = ''): void
    {
        Assert::assertNotTrue(...\func_get_args());
    }

    /**
     * Asserts that a condition is false.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert false $condition
     *
     * @see Assert::assertFalse
     */
    public static function assertFalse($condition, string $message = ''): void
    {
        Assert::assertFalse(...\func_get_args());
    }

    /**
     * Asserts that a condition is not false.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !false $condition
     *
     * @see Assert::assertNotFalse
     */
    public static function assertNotFalse($condition, string $message = ''): void
    {
        Assert::assertNotFalse(...\func_get_args());
    }

    /**
     * Asserts that a variable is null.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert null $actual
     *
     * @see Assert::assertNull
     */
    public static function assertNull($actual, string $message = ''): void
    {
        Assert::assertNull(...\func_get_args());
    }

    /**
     * Asserts that a variable is not null.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !null $actual
     *
     * @see Assert::assertNotNull
     */
    public static function assertNotNull($actual, string $message = ''): void
    {
        Assert::assertNotNull(...\func_get_args());
    }

    /**
     * Asserts that a variable is finite.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertFinite
     */
    public static function assertFinite($actual, string $message = ''): void
    {
        Assert::assertFinite(...\func_get_args());
    }

    /**
     * Asserts that a variable is infinite.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertInfinite
     */
    public static function assertInfinite($actual, string $message = ''): void
    {
        Assert::assertInfinite(...\func_get_args());
    }

    /**
     * Asserts that a variable is nan.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertNan
     */
    public static function assertNan($actual, string $message = ''): void
    {
        Assert::assertNan(...\func_get_args());
    }

    /**
     * Asserts that a class has a specified attribute.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertClassHasAttribute
     */
    public static function assertClassHasAttribute(string $attributeName, string $className, string $message = ''): void
    {
        Assert::assertClassHasAttribute(...\func_get_args());
    }

    /**
     * Asserts that a class does not have a specified attribute.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertClassNotHasAttribute
     */
    public static function assertClassNotHasAttribute(string $attributeName, string $className, string $message = ''): void
    {
        Assert::assertClassNotHasAttribute(...\func_get_args());
    }

    /**
     * Asserts that a class has a specified static attribute.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertClassHasStaticAttribute
     */
    public static function assertClassHasStaticAttribute(string $attributeName, string $className, string $message = ''): void
    {
        Assert::assertClassHasStaticAttribute(...\func_get_args());
    }

    /**
     * Asserts that a class does not have a specified static attribute.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertClassNotHasStaticAttribute
     */
    public static function assertClassNotHasStaticAttribute(string $attributeName, string $className, string $message = ''): void
    {
        Assert::assertClassNotHasStaticAttribute(...\func_get_args());
    }

    /**
     * Asserts that an object has a specified attribute.
     *
     * @param object $object
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertObjectHasAttribute
     */
    public static function assertObjectHasAttribute(string $attributeName, $object, string $message = ''): void
    {
        Assert::assertObjectHasAttribute(...\func_get_args());
    }

    /**
     * Asserts that an object does not have a specified attribute.
     *
     * @param object $object
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertObjectNotHasAttribute
     */
    public static function assertObjectNotHasAttribute(string $attributeName, $object, string $message = ''): void
    {
        Assert::assertObjectNotHasAttribute(...\func_get_args());
    }

    /**
     * Asserts that two variables have the same type and value.
     * Used on objects, it asserts that two variables reference
     * the same object.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-template ExpectedType
     * @psalm-param ExpectedType $expected
     * @psalm-assert =ExpectedType $actual
     *
     * @see Assert::assertSame
     */
    public static function assertSame($expected, $actual, string $message = ''): void
    {
        Assert::assertSame(...\func_get_args());
    }

    /**
     * Asserts that two variables do not have the same type and value.
     * Used on objects, it asserts that two variables do not reference
     * the same object.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertNotSame
     */
    public static function assertNotSame($expected, $actual, string $message = ''): void
    {
        Assert::assertNotSame(...\func_get_args());
    }

    /**
     * Asserts that a variable is of a given type.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $expected
     * @psalm-assert ExpectedType $actual
     *
     * @see Assert::assertInstanceOf
     */
    public static function assertInstanceOf(string $expected, $actual, string $message = ''): void
    {
        Assert::assertInstanceOf(...\func_get_args());
    }

    /**
     * Asserts that a variable is not of a given type.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $expected
     * @psalm-assert !ExpectedType $actual
     *
     * @see Assert::assertNotInstanceOf
     */
    public static function assertNotInstanceOf(string $expected, $actual, string $message = ''): void
    {
        Assert::assertNotInstanceOf(...\func_get_args());
    }

    /**
     * Asserts that a variable is of type array.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert array $actual
     *
     * @see Assert::assertIsArray
     */
    public static function assertIsArray($actual, string $message = ''): void
    {
        Assert::assertIsArray(...\func_get_args());
    }

    /**
     * Asserts that a variable is of type bool.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert bool $actual
     *
     * @see Assert::assertIsBool
     */
    public static function assertIsBool($actual, string $message = ''): void
    {
        Assert::assertIsBool(...\func_get_args());
    }

    /**
     * Asserts that a variable is of type float.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert float $actual
     *
     * @see Assert::assertIsFloat
     */
    public static function assertIsFloat($actual, string $message = ''): void
    {
        Assert::assertIsFloat(...\func_get_args());
    }

    /**
     * Asserts that a variable is of type int.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert int $actual
     *
     * @see Assert::assertIsInt
     */
    public static function assertIsInt($actual, string $message = ''): void
    {
        Assert::assertIsInt(...\func_get_args());
    }

    /**
     * Asserts that a variable is of type numeric.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert numeric $actual
     *
     * @see Assert::assertIsNumeric
     */
    public static function assertIsNumeric($actual, string $message = ''): void
    {
        Assert::assertIsNumeric(...\func_get_args());
    }

    /**
     * Asserts that a variable is of type object.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert object $actual
     *
     * @see Assert::assertIsObject
     */
    public static function assertIsObject($actual, string $message = ''): void
    {
        Assert::assertIsObject(...\func_get_args());
    }

    /**
     * Asserts that a variable is of type resource.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert resource $actual
     *
     * @see Assert::assertIsResource
     */
    public static function assertIsResource($actual, string $message = ''): void
    {
        Assert::assertIsResource(...\func_get_args());
    }

    /**
     * Asserts that a variable is of type string.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert string $actual
     *
     * @see Assert::assertIsString
     */
    public static function assertIsString($actual, string $message = ''): void
    {
        Assert::assertIsString(...\func_get_args());
    }

    /**
     * Asserts that a variable is of type scalar.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert scalar $actual
     *
     * @see Assert::assertIsScalar
     */
    public static function assertIsScalar($actual, string $message = ''): void
    {
        Assert::assertIsScalar(...\func_get_args());
    }

    /**
     * Asserts that a variable is of type callable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert callable $actual
     *
     * @see Assert::assertIsCallable
     */
    public static function assertIsCallable($actual, string $message = ''): void
    {
        Assert::assertIsCallable(...\func_get_args());
    }

    /**
     * Asserts that a variable is of type iterable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert iterable $actual
     *
     * @see Assert::assertIsIterable
     */
    public static function assertIsIterable($actual, string $message = ''): void
    {
        Assert::assertIsIterable(...\func_get_args());
    }

    /**
     * Asserts that a variable is not of type array.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !array $actual
     *
     * @see Assert::assertIsNotArray
     */
    public static function assertIsNotArray($actual, string $message = ''): void
    {
        Assert::assertIsNotArray(...\func_get_args());
    }

    /**
     * Asserts that a variable is not of type bool.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !bool $actual
     *
     * @see Assert::assertIsNotBool
     */
    public static function assertIsNotBool($actual, string $message = ''): void
    {
        Assert::assertIsNotBool(...\func_get_args());
    }

    /**
     * Asserts that a variable is not of type float.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !float $actual
     *
     * @see Assert::assertIsNotFloat
     */
    public static function assertIsNotFloat($actual, string $message = ''): void
    {
        Assert::assertIsNotFloat(...\func_get_args());
    }

    /**
     * Asserts that a variable is not of type int.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !int $actual
     *
     * @see Assert::assertIsNotInt
     */
    public static function assertIsNotInt($actual, string $message = ''): void
    {
        Assert::assertIsNotInt(...\func_get_args());
    }

    /**
     * Asserts that a variable is not of type numeric.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !numeric $actual
     *
     * @see Assert::assertIsNotNumeric
     */
    public static function assertIsNotNumeric($actual, string $message = ''): void
    {
        Assert::assertIsNotNumeric(...\func_get_args());
    }

    /**
     * Asserts that a variable is not of type object.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !object $actual
     *
     * @see Assert::assertIsNotObject
     */
    public static function assertIsNotObject($actual, string $message = ''): void
    {
        Assert::assertIsNotObject(...\func_get_args());
    }

    /**
     * Asserts that a variable is not of type resource.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !resource $actual
     *
     * @see Assert::assertIsNotResource
     */
    public static function assertIsNotResource($actual, string $message = ''): void
    {
        Assert::assertIsNotResource(...\func_get_args());
    }

    /**
     * Asserts that a variable is not of type string.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !string $actual
     *
     * @see Assert::assertIsNotString
     */
    public static function assertIsNotString($actual, string $message = ''): void
    {
        Assert::assertIsNotString(...\func_get_args());
    }

    /**
     * Asserts that a variable is not of type scalar.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !scalar $actual
     *
     * @see Assert::assertIsNotScalar
     */
    public static function assertIsNotScalar($actual, string $message = ''): void
    {
        Assert::assertIsNotScalar(...\func_get_args());
    }

    /**
     * Asserts that a variable is not of type callable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !callable $actual
     *
     * @see Assert::assertIsNotCallable
     */
    public static function assertIsNotCallable($actual, string $message = ''): void
    {
        Assert::assertIsNotCallable(...\func_get_args());
    }

    /**
     * Asserts that a variable is not of type iterable.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @psalm-assert !iterable $actual
     *
     * @see Assert::assertIsNotIterable
     */
    public static function assertIsNotIterable($actual, string $message = ''): void
    {
        Assert::assertIsNotIterable(...\func_get_args());
    }

    /**
     * Asserts that a string matches a given regular expression.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertMatchesRegularExpression
     */
    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        Assert::assertMatchesRegularExpression(...\func_get_args());
    }

    /**
     * Asserts that a string does not match a given regular expression.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertDoesNotMatchRegularExpression
     */
    public static function assertDoesNotMatchRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        Assert::assertDoesNotMatchRegularExpression(...\func_get_args());
    }

    /**
     * Assert that the size of two arrays (or `Countable` or `Traversable` objects)
     * is the same.
     *
     * @param \Countable|iterable $expected
     * @param \Countable|iterable $actual
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertSameSize
     */
    public static function assertSameSize($expected, $actual, string $message = ''): void
    {
        Assert::assertSameSize(...\func_get_args());
    }

    /**
     * Assert that the size of two arrays (or `Countable` or `Traversable` objects)
     * is not the same.
     *
     * @param \Countable|iterable $expected
     * @param \Countable|iterable $actual
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertNotSameSize
     */
    public static function assertNotSameSize($expected, $actual, string $message = ''): void
    {
        Assert::assertNotSameSize(...\func_get_args());
    }

    /**
     * Asserts that a string matches a given format string.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringMatchesFormat
     */
    public static function assertStringMatchesFormat(string $format, string $string, string $message = ''): void
    {
        Assert::assertStringMatchesFormat(...\func_get_args());
    }

    /**
     * Asserts that a string does not match a given format string.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringNotMatchesFormat
     */
    public static function assertStringNotMatchesFormat(string $format, string $string, string $message = ''): void
    {
        Assert::assertStringNotMatchesFormat(...\func_get_args());
    }

    /**
     * Asserts that a string matches a given format file.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringMatchesFormatFile
     */
    public static function assertStringMatchesFormatFile(string $formatFile, string $string, string $message = ''): void
    {
        Assert::assertStringMatchesFormatFile(...\func_get_args());
    }

    /**
     * Asserts that a string does not match a given format string.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringNotMatchesFormatFile
     */
    public static function assertStringNotMatchesFormatFile(string $formatFile, string $string, string $message = ''): void
    {
        Assert::assertStringNotMatchesFormatFile(...\func_get_args());
    }

    /**
     * Asserts that a string starts with a given prefix.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringStartsWith
     */
    public static function assertStringStartsWith(string $prefix, string $string, string $message = ''): void
    {
        Assert::assertStringStartsWith(...\func_get_args());
    }

    /**
     * Asserts that a string starts not with a given prefix.
     *
     * @param string $prefix
     * @param string $string
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringStartsNotWith
     */
    public static function assertStringStartsNotWith($prefix, $string, string $message = ''): void
    {
        Assert::assertStringStartsNotWith(...\func_get_args());
    }

    /**
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringContainsString
     */
    public static function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        Assert::assertStringContainsString(...\func_get_args());
    }

    /**
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringContainsStringIgnoringCase
     */
    public static function assertStringContainsStringIgnoringCase(string $needle, string $haystack, string $message = ''): void
    {
        Assert::assertStringContainsStringIgnoringCase(...\func_get_args());
    }

    /**
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringNotContainsString
     */
    public static function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void
    {
        Assert::assertStringNotContainsString(...\func_get_args());
    }

    /**
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringNotContainsStringIgnoringCase
     */
    public static function assertStringNotContainsStringIgnoringCase(string $needle, string $haystack, string $message = ''): void
    {
        Assert::assertStringNotContainsStringIgnoringCase(...\func_get_args());
    }

    /**
     * Asserts that a string ends with a given suffix.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringEndsWith
     */
    public static function assertStringEndsWith(string $suffix, string $string, string $message = ''): void
    {
        Assert::assertStringEndsWith(...\func_get_args());
    }

    /**
     * Asserts that a string ends not with a given suffix.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertStringEndsNotWith
     */
    public static function assertStringEndsNotWith(string $suffix, string $string, string $message = ''): void
    {
        Assert::assertStringEndsNotWith(...\func_get_args());
    }

    /**
     * Asserts that two XML files are equal.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertXmlFileEqualsXmlFile
     */
    public static function assertXmlFileEqualsXmlFile(string $expectedFile, string $actualFile, string $message = ''): void
    {
        Assert::assertXmlFileEqualsXmlFile(...\func_get_args());
    }

    /**
     * Asserts that two XML files are not equal.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertXmlFileNotEqualsXmlFile
     */
    public static function assertXmlFileNotEqualsXmlFile(string $expectedFile, string $actualFile, string $message = ''): void
    {
        Assert::assertXmlFileNotEqualsXmlFile(...\func_get_args());
    }

    /**
     * Asserts that two XML documents are equal.
     *
     * @param \DOMDocument|string $actualXml
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertXmlStringEqualsXmlFile
     */
    public static function assertXmlStringEqualsXmlFile(string $expectedFile, $actualXml, string $message = ''): void
    {
        Assert::assertXmlStringEqualsXmlFile(...\func_get_args());
    }

    /**
     * Asserts that two XML documents are not equal.
     *
     * @param \DOMDocument|string $actualXml
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertXmlStringNotEqualsXmlFile
     */
    public static function assertXmlStringNotEqualsXmlFile(string $expectedFile, $actualXml, string $message = ''): void
    {
        Assert::assertXmlStringNotEqualsXmlFile(...\func_get_args());
    }

    /**
     * Asserts that two XML documents are equal.
     *
     * @param \DOMDocument|string $expectedXml
     * @param \DOMDocument|string $actualXml
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertXmlStringEqualsXmlString
     */
    public static function assertXmlStringEqualsXmlString($expectedXml, $actualXml, string $message = ''): void
    {
        Assert::assertXmlStringEqualsXmlString(...\func_get_args());
    }

    /**
     * Asserts that two XML documents are not equal.
     *
     * @param \DOMDocument|string $expectedXml
     * @param \DOMDocument|string $actualXml
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Exception
     *
     * @see Assert::assertXmlStringNotEqualsXmlString
     */
    public static function assertXmlStringNotEqualsXmlString($expectedXml, $actualXml, string $message = ''): void
    {
        Assert::assertXmlStringNotEqualsXmlString(...\func_get_args());
    }

    /**
     * Evaluates a PHPUnit\Framework\Constraint matcher object.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertThat
     */
    public static function assertThat($value, Constraint $constraint, string $message = ''): void
    {
        Assert::assertThat(...\func_get_args());
    }

    /**
     * Asserts that a string is a valid JSON string.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertJson
     */
    public static function assertJson(string $actualJson, string $message = ''): void
    {
        Assert::assertJson(...\func_get_args());
    }

    /**
     * Asserts that two given JSON encoded objects or arrays are equal.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertJsonStringEqualsJsonString
     */
    public static function assertJsonStringEqualsJsonString(string $expectedJson, string $actualJson, string $message = ''): void
    {
        Assert::assertJsonStringEqualsJsonString(...\func_get_args());
    }

    /**
     * Asserts that two given JSON encoded objects or arrays are not equal.
     *
     * @param string $expectedJson
     * @param string $actualJson
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertJsonStringNotEqualsJsonString
     */
    public static function assertJsonStringNotEqualsJsonString($expectedJson, $actualJson, string $message = ''): void
    {
        Assert::assertJsonStringNotEqualsJsonString(...\func_get_args());
    }

    /**
     * Asserts that the generated JSON encoded object and the content of the given file are equal.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertJsonStringEqualsJsonFile
     */
    public static function assertJsonStringEqualsJsonFile(string $expectedFile, string $actualJson, string $message = ''): void
    {
        Assert::assertJsonStringEqualsJsonFile(...\func_get_args());
    }

    /**
     * Asserts that the generated JSON encoded object and the content of the given file are not equal.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertJsonStringNotEqualsJsonFile
     */
    public static function assertJsonStringNotEqualsJsonFile(string $expectedFile, string $actualJson, string $message = ''): void
    {
        Assert::assertJsonStringNotEqualsJsonFile(...\func_get_args());
    }

    /**
     * Asserts that two JSON files are equal.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertJsonFileEqualsJsonFile
     */
    public static function assertJsonFileEqualsJsonFile(string $expectedFile, string $actualFile, string $message = ''): void
    {
        Assert::assertJsonFileEqualsJsonFile(...\func_get_args());
    }

    /**
     * Asserts that two JSON files are not equal.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @see Assert::assertJsonFileNotEqualsJsonFile
     */
    public static function assertJsonFileNotEqualsJsonFile(string $expectedFile, string $actualFile, string $message = ''): void
    {
        Assert::assertJsonFileNotEqualsJsonFile(...\func_get_args());
    }

    public static function logicalAnd(): LogicalAnd
    {
        return Assert::logicalAnd(...\func_get_args());
    }

    public static function logicalOr(): LogicalOr
    {
        return Assert::logicalOr(...\func_get_args());
    }

    public static function logicalNot(Constraint $constraint): LogicalNot
    {
        return Assert::logicalNot(...\func_get_args());
    }

    public static function logicalXor(): LogicalXor
    {
        return Assert::logicalXor(...\func_get_args());
    }

    public static function anything(): IsAnything
    {
        return Assert::anything(...\func_get_args());
    }

    public static function isTrue(): IsTrue
    {
        return Assert::isTrue(...\func_get_args());
    }

    public static function callback(callable $callback): Callback
    {
        return Assert::callback(...\func_get_args());
    }

    public static function isFalse(): IsFalse
    {
        return Assert::isFalse(...\func_get_args());
    }

    public static function isJson(): IsJson
    {
        return Assert::isJson(...\func_get_args());
    }

    public static function isNull(): IsNull
    {
        return Assert::isNull(...\func_get_args());
    }

    public static function isFinite(): IsFinite
    {
        return Assert::isFinite(...\func_get_args());
    }

    public static function isInfinite(): IsInfinite
    {
        return Assert::isInfinite(...\func_get_args());
    }

    public static function isNan(): IsNan
    {
        return Assert::isNan(...\func_get_args());
    }

    public static function containsEqual($value): TraversableContainsEqual
    {
        return Assert::containsEqual(...\func_get_args());
    }

    public static function containsIdentical($value): TraversableContainsIdentical
    {
        return Assert::containsIdentical(...\func_get_args());
    }

    public static function containsOnly(string $type): TraversableContainsOnly
    {
        return Assert::containsOnly(...\func_get_args());
    }

    public static function containsOnlyInstancesOf(string $className): TraversableContainsOnly
    {
        return Assert::containsOnlyInstancesOf(...\func_get_args());
    }

    public static function arrayHasKey($key): ArrayHasKey
    {
        return Assert::arrayHasKey(...\func_get_args());
    }

    public static function equalTo($value): IsEqual
    {
        return Assert::equalTo(...\func_get_args());
    }

    public static function equalToCanonicalizing($value): IsEqualCanonicalizing
    {
        return Assert::equalToCanonicalizing(...\func_get_args());
    }

    public static function equalToIgnoringCase($value): IsEqualIgnoringCase
    {
        return Assert::equalToIgnoringCase(...\func_get_args());
    }

    public static function equalToWithDelta($value, float $delta): IsEqualWithDelta
    {
        return Assert::equalToWithDelta(...\func_get_args());
    }

    public static function isEmpty(): IsEmpty
    {
        return Assert::isEmpty(...\func_get_args());
    }

    public static function isWritable(): IsWritable
    {
        return Assert::isWritable(...\func_get_args());
    }

    public static function isReadable(): IsReadable
    {
        return Assert::isReadable(...\func_get_args());
    }

    public static function directoryExists(): DirectoryExists
    {
        return Assert::directoryExists(...\func_get_args());
    }

    public static function fileExists(): FileExists
    {
        return Assert::fileExists(...\func_get_args());
    }

    public static function greaterThan($value): GreaterThan
    {
        return Assert::greaterThan(...\func_get_args());
    }

    public static function greaterThanOrEqual($value): LogicalOr
    {
        return Assert::greaterThanOrEqual(...\func_get_args());
    }

    public static function classHasAttribute(string $attributeName): ClassHasAttribute
    {
        return Assert::classHasAttribute(...\func_get_args());
    }

    public static function classHasStaticAttribute(string $attributeName): ClassHasStaticAttribute
    {
        return Assert::classHasStaticAttribute(...\func_get_args());
    }

    public static function objectHasAttribute($attributeName): ObjectHasAttribute
    {
        return Assert::objectHasAttribute(...\func_get_args());
    }

    public static function identicalTo($value): IsIdentical
    {
        return Assert::identicalTo(...\func_get_args());
    }

    public static function isInstanceOf(string $className): IsInstanceOf
    {
        return Assert::isInstanceOf(...\func_get_args());
    }

    public static function isType(string $type): IsType
    {
        return Assert::isType(...\func_get_args());
    }

    public static function lessThan($value): LessThan
    {
        return Assert::lessThan(...\func_get_args());
    }

    public static function lessThanOrEqual($value): LogicalOr
    {
        return Assert::lessThanOrEqual(...\func_get_args());
    }

    public static function matchesRegularExpression(string $pattern): RegularExpression
    {
        return Assert::matchesRegularExpression(...\func_get_args());
    }

    public static function matches(string $string): StringMatchesFormatDescription
    {
        return Assert::matches(...\func_get_args());
    }

    public static function stringStartsWith($prefix): StringStartsWith
    {
        return Assert::stringStartsWith(...\func_get_args());
    }

    public static function stringContains(string $string, bool $case = true): StringContains
    {
        return Assert::stringContains(...\func_get_args());
    }

    public static function stringEndsWith(string $suffix): StringEndsWith
    {
        return Assert::stringEndsWith(...\func_get_args());
    }

    public static function countOf(int $count): Count
    {
        return Assert::countOf(...\func_get_args());
    }

    /**
     * Returns a matcher that matches when the method is executed
     * zero or more times.
     */
    public static function any(): AnyInvokedCountMatcher
    {
        return new AnyInvokedCountMatcher();
    }

    /**
     * Returns a matcher that matches when the method is never executed.
     */
    public static function never(): InvokedCountMatcher
    {
        return new InvokedCountMatcher(0);
    }

    /**
     * Returns a matcher that matches when the method is executed
     * at least N times.
     */
    public static function atLeast(int $requiredInvocations): InvokedAtLeastCountMatcher
    {
        return new InvokedAtLeastCountMatcher(
            $requiredInvocations
        );
    }

    /**
     * Returns a matcher that matches when the method is executed at least once.
     */
    public static function atLeastOnce(): InvokedAtLeastOnceMatcher
    {
        return new InvokedAtLeastOnceMatcher();
    }

    /**
     * Returns a matcher that matches when the method is executed exactly once.
     */
    public static function once(): InvokedCountMatcher
    {
        return new InvokedCountMatcher(1);
    }

    /**
     * Returns a matcher that matches when the method is executed
     * exactly $count times.
     */
    public static function exactly(int $count): InvokedCountMatcher
    {
        return new InvokedCountMatcher($count);
    }

    /**
     * Returns a matcher that matches when the method is executed
     * at most N times.
     */
    public static function atMost(int $allowedInvocations): InvokedAtMostCountMatcher
    {
        return new InvokedAtMostCountMatcher($allowedInvocations);
    }

    /**
     * Returns a matcher that matches when the method is executed
     * at the given index.
     */
    public static function at(int $index): InvokedAtIndexMatcher
    {
        return new InvokedAtIndexMatcher($index);
    }

    public static function returnValue($value): ReturnStub
    {
        return new ReturnStub($value);
    }

    public static function returnValueMap(array $valueMap): ReturnValueMapStub
    {
        return new ReturnValueMapStub($valueMap);
    }

    public static function returnArgument(int $argumentIndex): ReturnArgumentStub
    {
        return new ReturnArgumentStub($argumentIndex);
    }

    public static function returnCallback($callback): ReturnCallbackStub
    {
        return new ReturnCallbackStub($callback);
    }

    /**
     * Returns the current object.
     *
     * This method is useful when mocking a fluent interface.
     */
    public static function returnSelf(): ReturnSelfStub
    {
        return new ReturnSelfStub();
    }

    public static function throwException(\Throwable $exception): ExceptionStub
    {
        return new ExceptionStub($exception);
    }

    public static function onConsecutiveCalls(): ConsecutiveCallsStub
    {
        $args = \func_get_args();

        return new ConsecutiveCallsStub($args);
    }

    /**
     * Mark the test as incomplete.
     *
     * @throws IncompleteTestError
     *
     * @psalm-return never-return
     */
    public static function markTestIncomplete(string $message = ''): void
    {
        Assert::markTestIncomplete($message);
    }

    /**
     * Mark the test as skipped.
     *
     * @throws SkippedTestError
     * @throws SyntheticSkippedError
     *
     * @psalm-return never-return
     */
    public static function markTestSkipped(string $message = ''): void
    {
        Assert::markTestSkipped($message);
    }

    /**
     * Fails a test with the given message.
     *
     * @throws AssertionFailedError
     *
     * @psalm-return never-return
     */
    public static function fail(string $message = ''): void
    {
        Assert::fail($message);
    }
}
