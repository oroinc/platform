<?php
declare(strict_types=1);

namespace Oro\Component\Log\Test;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory as ComparatorFactory;

/**
 * Asserts that the log message context it is applied to contains the specified data.
 */
class LoggerContextConstraint extends Constraint
{
    private array $expectedContextData;
    private ?string $expectedCalledIn;
    /** @var \Throwable|null|false|string */
    private $expectedPreviousException;

    private bool $failedCalledInPresent = false;
    private bool $failedCalledInEquals = false;
    private bool $failedPreviousException = false;
    private string $failedPreviousExceptionDescription = '';
    private bool $failedContextData = false;
    private ?ComparisonFailure $failedContextDataComparisonFailure = null;

    /**
     * The $expectedPreviousException parameter accepts an exception instance, null, boolean false or classname string:
     * - use false (default value), if you need to make sure that there is no 'exception' in the context,
     *   or if you are asserting the 'exception' key value as part of the context data assertion;
     * - use an exception instance for strict object comparison;
     * - use classname string to assert only if the exception is an instance of the certain class;
     * - use null to ignore 'exception' in the context even if it is present.
     *
     * @param array $expectedContextData
     * @param \Throwable|null|false|string $expectedPreviousException
     * @param string|null $expectedCalledIn
     */
    public function __construct(
        array $expectedContextData,
        $expectedPreviousException = false,
        string $expectedCalledIn = null
    ) {
        $this->expectedContextData = $expectedContextData;
        $this->expectedCalledIn = $expectedCalledIn;

        if (false !== $expectedPreviousException
            && null !== $expectedPreviousException
            && !($expectedPreviousException instanceof \Throwable)
            && !(\is_string($expectedPreviousException) && \class_exists($expectedPreviousException))
        ) {
            throw new \InvalidArgumentException(\sprintf(
                '$previousException accepts only false, null, classname string or an instance of \Throwable, %s given',
                \gettype($expectedPreviousException)
            ));
        }

        $this->expectedPreviousException = $expectedPreviousException;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function evaluate($other, string $description = '', bool $returnResult = false): ?bool
    {
        $this->failedCalledInPresent = !isset($other['called_in']);
        $this->failedCalledInEquals = null !== $this->expectedCalledIn
            && $this->expectedCalledIn !== ($other['called_in'] ?? null);
        unset($other['called_in']);

        $this->comparePreviousException($other);
        if (false !== $this->expectedPreviousException) {
            unset($other['exception']);
        }

        $this->compareContextData($other);

        if ($this->failedCalledInPresent
            || $this->failedCalledInEquals
            || $this->failedPreviousException
            || $this->failedContextData
        ) {
            if ($returnResult) {
                return false;
            }
            throw new ExpectationFailedException(
                \trim($description . "\n" . $this->toString()),
                $this->failedContextDataComparisonFailure
            );
        }

        return true;
    }

    public function toString(): string
    {
        $description = [];

        if ($this->failedCalledInPresent) {
            $description[] = "the context['called_in'] value is set";
        }

        if ($this->failedCalledInEquals) {
            $description[] = \sprintf("the context['called_in'] value matches '%s'", $this->expectedCalledIn);
        }

        if ($this->failedPreviousException) {
            $description[] = $this->failedPreviousExceptionDescription;
        }

        if ($this->failedContextData) {
            $description[] = \sprintf(
                ($this->failedCalledInPresent || $this->failedCalledInEquals || $this->failedPreviousException
                    ? 'the rest of '
                    : '')
                . 'the context data is equal to %s',
                $this->exporter()->export($this->expectedContextData),
            );
        }

        return 'Failed asserting that ' . \implode("\n and ", $description);
    }

    /**
     * Compares the rest of the context data using PHPUnit's native comparator,
     * which produces a nice array diff on comparison failure.
     */
    private function compareContextData($other): void
    {
        $comparatorFactory = ComparatorFactory::getInstance();
        $comparator = $comparatorFactory->getComparatorFor($this->expectedContextData, $other);
        try {
            $comparator->assertEquals($this->expectedContextData, $other);
        } catch (ComparisonFailure $f) {
            $this->failedContextData = true;
            $this->failedContextDataComparisonFailure = $f;
        }
    }

    private function comparePreviousException($other): void
    {
        if (null === $this->expectedPreviousException) {
            return;
        }

        if (false === $this->expectedPreviousException) {
            if (isset($other['exception'])) {
                $this->failedPreviousException = true;
                $this->failedPreviousExceptionDescription = "the context['exception'] value is not set";
            }
            return;
        }

        if (\is_string($this->expectedPreviousException)) {
            if (!isset($other['exception'])) {
                $this->failedPreviousException = true;
                $this->failedPreviousExceptionDescription = \sprintf(
                    "the context['exception'] value is set and is an instance of %s",
                    $this->expectedPreviousException
                );
                return;
            }
            if (!($other['exception'] instanceof $this->expectedPreviousException)) {
                $this->failedPreviousException = true;
                $this->failedPreviousExceptionDescription = \sprintf(
                    "the context['exception'] value is an instance of %s",
                    $this->expectedPreviousException
                );
            }
            return;
        }

        if (!isset($other['exception'])) {
            $this->failedPreviousException = true;
            $this->failedPreviousExceptionDescription = \sprintf(
                "the context['exception'] value is set and is identical to the expected instance of %s",
                \get_class($this->expectedPreviousException)
            );
            return;
        }

        if ($this->expectedPreviousException !== $other['exception']) {
            $this->failedPreviousException = true;
            $this->failedPreviousExceptionDescription = \sprintf(
                "the context['exception'] value is identical to the expected instance of %s",
                \get_class($this->expectedPreviousException)
            );
        }
    }
}
