<?php

namespace Oro\Component\Testing\Assert;

/**
 * Constraint that asserts that the array contains an expected array.
 */
class ArrayContainsConstraint extends \PHPUnit\Framework\Constraint\Constraint
{
    /** @var array */
    private $expected;

    /** @var bool */
    private $strict;

    /** @var array [[path, message], ...] */
    private $errors = [];

    /**
     * @param array $expected The expected array
     * @param bool  $strict   Whether the order of elements in an array is important
     */
    public function __construct(array $expected, $strict = true)
    {
        parent::__construct();
        $this->expected = $expected;
        $this->strict = $strict;
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($other)
    {
        $this->matchArrayContains($this->expected, $other, []);

        return empty($this->errors);
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'the array contains other array';
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other)
    {
        return $this->toString();
    }

    /**
     * {@inheritdoc}
     */
    protected function additionalFailureDescription($other)
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
    private function matchArrayContains(array $expected, $actual, array $path)
    {
        if (!$this->isInternalType('array', $actual, $path)) {
            return;
        }

        if ($this->strict || $this->isAssocArray($expected)) {
            $this->matchAssocArray($expected, $actual, $path);
        } else {
            $this->matchIndexedArray($expected, $actual, $path);
        }
    }

    /**
     * @param array $expected
     * @param array $actual
     * @param array $path
     */
    private function matchAssocArray(array $expected, array $actual, array $path)
    {
        $lastPathIndex = count($path);
        foreach ($expected as $expectedKey => $expectedValue) {
            $path[$lastPathIndex] = $expectedKey;
            if (!$this->isArrayHasKey($expectedKey, $actual, $path)) {
                continue;
            }
            $this->matchArrayElement($expectedValue, $actual[$expectedKey], $path);
        }
    }

    /**
     * @param array $expected
     * @param array $actual
     * @param array $path
     */
    private function matchIndexedArray(array $expected, array $actual, array $path)
    {
        $processedKeys = [];
        $lastPathIndex = count($path);
        $expectedPath = $path;
        foreach ($expected as $expectedKey => $expectedValue) {
            if (in_array($expectedKey, $processedKeys, true)) {
                continue;
            }
            $expectedPath[$lastPathIndex] = $expectedKey;
            if (!$this->isArrayHasKey($expectedKey, $actual, $expectedPath)) {
                $processedKeys[] = $expectedKey;
                continue;
            }

            $errors = $this->errors;
            $this->matchArrayElement($expectedValue, $actual[$expectedKey], $expectedPath);
            if (count($errors) === count($this->errors)) {
                $processedKeys[] = $expectedKey;
            } else {
                $elementErrors = array_slice($this->errors, count($errors));
                $this->errors = $errors;
                $key = $this->tryMatchIndexedElement($expectedKey, $expectedValue, $actual, $path, $processedKeys);
                if (null === $key) {
                    $this->errors = array_merge($errors, $elementErrors);
                } else {
                    $processedKeys[] = $key;
                }
            }
        }
    }

    /**
     * @param int   $expectedKey
     * @param mixed $expectedValue
     * @param array $actual
     * @param array $path
     * @param int[] $processedKeys
     *
     * @return int|null
     */
    private function tryMatchIndexedElement(
        $expectedKey,
        $expectedValue,
        array $actual,
        array $path,
        array $processedKeys
    ) {
        $foundKey = null;
        $elementPath = $path;
        $lastPathIndex = count($path);
        foreach ($actual as $key => $value) {
            if ($key === $expectedKey || in_array($key, $processedKeys, true)) {
                continue;
            }
            $errors = $this->errors;
            $elementPath[$lastPathIndex] = $key;
            $this->matchArrayElement($expectedValue, $value, $elementPath);
            if (count($errors) === count($this->errors)) {
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
    private function matchArrayElement($expectedValue, $value, array $path)
    {
        if (is_array($expectedValue)) {
            $this->matchArrayContains($expectedValue, $value, $path);
        } else {
            $this->isSame($expectedValue, $value, $path);
        }
    }

    /**
     * @param string   $expectedType
     * @param array    $value
     * @param string[] $path
     *
     * @return bool
     */
    private function isInternalType($expectedType, $value, array $path)
    {
        try {
            \PHPUnit\Framework\Assert::assertInternalType($expectedType, $value);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $this->errors[] = [$path, $e->getMessage()];

            return false;
        }

        return true;
    }

    /**
     * @param mixed    $key
     * @param array    $array
     * @param string[] $path
     *
     * @return bool
     */
    private function isArrayHasKey($key, $array, array $path)
    {
        try {
            \PHPUnit\Framework\Assert::assertArrayHasKey($key, $array);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $this->errors[] = [$path, $e->getMessage()];

            return false;
        }

        return true;
    }

    /**
     * @param mixed    $expected
     * @param mixed    $actual
     * @param string[] $path
     *
     * @return bool
     */
    private function isSame($expected, $actual, array $path)
    {
        try {
            if (is_string($actual) && is_string($expected)) {
                if ($actual !== $expected) {
                    throw new \PHPUnit\Framework\ExpectationFailedException(sprintf(
                        'Failed asserting that \'%s\' is identical to \'%s\'.',
                        $actual,
                        $expected
                    ));
                }
            } else {
                \PHPUnit\Framework\Assert::assertSame($expected, $actual);
            }
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $this->errors[] = [$path, $e->getMessage()];

            return false;
        }

        return true;
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    private function isAssocArray(array $array)
    {
        return array_values($array) !== $array;
    }
}
