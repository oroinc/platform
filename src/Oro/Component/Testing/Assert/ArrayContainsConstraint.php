<?php

namespace Oro\Component\Testing\Assert;

/**
 * Constraint that asserts that the array contains an expected array.
 */
class ArrayContainsConstraint extends \PHPUnit\Framework\Constraint\Constraint
{
    protected array $expected;
    protected bool $strict;
    private array $useStartsWithComparison = [];
    /** @var array [[path, message], ...] */
    protected array $errors = [];

    /**
     * @param array $expected The expected array
     * @param bool  $strict   Whether the order of elements in an array is important
     */
    public function __construct(array $expected, bool $strict = true)
    {
        $this->expected = $expected;
        $this->strict = $strict;
    }

    public function useStartsWithComparison(string $propertyPath, bool $useStartsWithComparison = true): void
    {
        if ($useStartsWithComparison) {
            $this->useStartsWithComparison[$propertyPath] = true;
        } else {
            unset($this->useStartsWithComparison[$propertyPath]);
        }
    }

    #[\Override]
    protected function matches($other): bool
    {
        $this->matchArrayContains($this->expected, $other, []);

        return empty($this->errors);
    }

    #[\Override]
    public function toString(): string
    {
        return 'the array contains other array';
    }

    #[\Override]
    protected function failureDescription($other): string
    {
        return $this->toString();
    }

    #[\Override]
    protected function additionalFailureDescription($other): string
    {
        $result = "Errors:\n";
        $i = 0;
        foreach ($this->errors as $error) {
            $i++;
            if ($i > 10) {
                $result .= "and others ...\n";
                break;
            }
            $result .= sprintf("Path: \"%s\". Error: %s\n", implode('.', $error[0]), $error[1]);
        }

        return $result;
    }

    /**
     * @param array    $expected
     * @param mixed    $actual
     * @param string[] $path
     */
    protected function matchArrayContains(array $expected, mixed $actual, array $path): void
    {
        if (!$this->isArray($actual, $path)) {
            return;
        }

        if ($this->strict || $this->isAssocArray($expected)) {
            $this->matchAssocArray($expected, $actual, $path);
        } else {
            $this->matchIndexedArray($expected, $actual, $path);
        }
    }

    protected function matchAssocArray(array $expected, array $actual, array $path): void
    {
        $lastPathIndex = \count($path);
        foreach ($expected as $expectedKey => $expectedValue) {
            $path[$lastPathIndex] = $expectedKey;
            if (!$this->isArrayHasKey($expectedKey, $actual, $path)) {
                continue;
            }
            $this->matchArrayElement($expectedValue, $actual[$expectedKey], $path);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function matchIndexedArray(array $expected, array $actual, array $path): void
    {
        $processedKeys = []; // [found key => expected key, ...]
        $lastPathIndex = \count($path);
        $expectedPath = $path;

        // 1. try to match expected and actual elements with the same index
        foreach ($expected as $expectedKey => $expectedValue) {
            if (array_key_exists($expectedKey, $actual)) {
                $expectedPath[$lastPathIndex] = $expectedKey;
                $errors = $this->errors;
                $this->matchArrayElement($expectedValue, $actual[$expectedKey], $expectedPath);
                if (\count($errors) === \count($this->errors)) {
                    $processedKeys[$expectedKey] = $expectedKey;
                } else {
                    $this->errors = $errors;
                }
            }
        }

        // 2. try to match expected elements that do not have appropriate actual elements with the same index
        // this is required because the order of elements should not be matter
        foreach ($expected as $expectedKey => $expectedValue) {
            if (array_key_exists($expectedKey, $actual) && !in_array($expectedKey, $processedKeys, true)) {
                $key = $this->tryMatchIndexedElement($expectedValue, $actual, $path, $processedKeys);
                if (null !== $key) {
                    $processedKeys[$key] = $expectedKey;
                }
            }
        }

        // 3. add errors for unmatched elements, including extra elements in expected data
        foreach ($expected as $expectedKey => $expectedValue) {
            if (!in_array($expectedKey, $processedKeys, true)) {
                $expectedPath[$lastPathIndex] = $expectedKey;
                if ($this->isArrayHasKey($expectedKey, $actual, $expectedPath)) {
                    $this->matchArrayElement($expectedValue, $actual[$expectedKey], $expectedPath);
                }
                $processedKeys[$expectedKey] = $expectedKey;
            }
        }
    }

    /**
     * @param mixed $expectedValue
     * @param array $actual
     * @param array $path
     * @param int[] $processedKeys
     *
     * @return int|null
     */
    protected function tryMatchIndexedElement(
        mixed $expectedValue,
        array $actual,
        array $path,
        array $processedKeys
    ): ?int {
        $foundKey = null;
        $elementPath = $path;
        $lastPathIndex = \count($path);
        foreach ($actual as $key => $value) {
            if (isset($processedKeys[$key])) {
                continue;
            }
            $errors = $this->errors;
            $elementPath[$lastPathIndex] = $key;
            $this->matchArrayElement($expectedValue, $value, $elementPath);
            if (\count($errors) === \count($this->errors)) {
                $foundKey = $key;
                break;
            }
            $this->errors = $errors;
        }

        return $foundKey;
    }

    /**
     * @param mixed    $expectedValue
     * @param mixed    $value
     * @param string[] $path
     */
    protected function matchArrayElement(mixed $expectedValue, mixed $value, array $path): void
    {
        if (\is_array($expectedValue)) {
            $this->matchArrayContains($expectedValue, $value, $path);
        } else {
            $this->isSame($expectedValue, $value, $path);
        }
    }

    protected function isArray(mixed $value, array $path): bool
    {
        try {
            \PHPUnit\Framework\Assert::assertIsArray($value);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $this->errors[] = [$path, $e->getMessage()];

            return false;
        }

        return true;
    }

    protected function isArrayHasKey(mixed $key, array $array, array $path): bool
    {
        try {
            \PHPUnit\Framework\Assert::assertArrayHasKey($key, $array);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $this->errors[] = [$path, $e->getMessage()];

            return false;
        }

        return true;
    }

    protected function isSame(mixed $expected, mixed $actual, array $path): bool
    {
        try {
            if (\is_string($actual) && \is_string($expected)) {
                $propertyPath = $this->getPropertyPath($path);
                if (isset($this->useStartsWithComparison[$propertyPath])) {
                    \PHPUnit\Framework\Assert::assertStringStartsWith($expected, $actual);
                } elseif ($actual !== $expected) {
                    throw new \PHPUnit\Framework\ExpectationFailedException(sprintf(
                        'Failed asserting that \'%s\' is identical to \'%s\'.',
                        $actual,
                        $expected
                    ));
                }
            } elseif (\is_float($expected)) {
                \PHPUnit\Framework\Assert::assertEqualsWithDelta($expected, $actual, 0.0000001);
            } else {
                \PHPUnit\Framework\Assert::assertSame($expected, $actual);
            }
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $this->errors[] = [$path, $e->getMessage()];

            return false;
        }

        return true;
    }

    protected function isAssocArray(array $array): bool
    {
        return !array_is_list($array);
    }

    private function getPropertyPath(array $path): string
    {
        return implode('.', array_filter($path, static function ($item) {
            return !is_numeric($item);
        }));
    }
}
