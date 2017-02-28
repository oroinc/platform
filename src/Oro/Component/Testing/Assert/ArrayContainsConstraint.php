<?php

namespace Oro\Component\Testing\Assert;

/**
 * Constraint that asserts that the array contains an expected array.
 */
class ArrayContainsConstraint extends \PHPUnit_Framework_Constraint
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
                continue;
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

            $initialErrorCount = count($this->errors);
            $this->matchArrayElement($expectedValue, $actual[$expectedKey], $expectedPath);
            if (count($this->errors) === $initialErrorCount) {
                $processedKeys[] = $expectedKey;
            } else {
                $this->tryMatchIndexedElement(
                    $expectedKey,
                    $expectedValue,
                    $actual,
                    $path,
                    $initialErrorCount,
                    $processedKeys
                );
            }
        }
    }

    /**
     * @param int   $expectedKey
     * @param mixed $expectedValue
     * @param array $actual
     * @param array $path
     * @param int   $initialErrorCount
     * @param int[] $processedKeys
     */
    private function tryMatchIndexedElement(
        $expectedKey,
        $expectedValue,
        array $actual,
        array $path,
        $initialErrorCount,
        array &$processedKeys
    ) {
        $isElementFound = false;
        $elementPath = $path;
        $elementErrorCount = count($this->errors);
        $elementErrors = array_slice($this->errors, $initialErrorCount, $elementErrorCount - $initialErrorCount);
        $this->errors = array_slice($this->errors, 0, -$initialErrorCount);
        $lastPathIndex = count($path);
        foreach ($actual as $key => $value) {
            if ($key === $expectedKey || in_array($key, $processedKeys, true)) {
                continue;
            }
            $elementPath[$lastPathIndex] = $key;
            $this->matchArrayElement($expectedValue, $value, $elementPath);
            $errorCount = count($this->errors);
            if ($errorCount === $initialErrorCount) {
                $processedKeys[] = $key;
                $isElementFound = true;
                break;
            }
            if ($errorCount < $elementErrorCount) {
                $elementErrorCount = $errorCount;
                $elementErrors = array_slice($this->errors, $initialErrorCount, $errorCount - $initialErrorCount);
            }
            $this->errors = array_slice($this->errors, 0, -$initialErrorCount);
        }
        if (!$isElementFound) {
            $this->errors = array_merge($this->errors, $elementErrors);
        }
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
            \PHPUnit_Framework_Assert::assertInternalType($expectedType, $value);
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
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
            \PHPUnit_Framework_Assert::assertArrayHasKey($key, $array);
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
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
            \PHPUnit_Framework_Assert::assertSame($expected, $actual);
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
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
