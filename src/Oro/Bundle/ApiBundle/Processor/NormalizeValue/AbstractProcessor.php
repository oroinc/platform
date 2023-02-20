<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Provides a base implementation for different kinds of a value normalization processors.
 */
abstract class AbstractProcessor implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var NormalizeValueContext $context */

        if (!$context->hasRequirement()) {
            $this->processRequirement($context);
        }
        if ($context->hasResult()) {
            $this->processNormalization($context);
        }
    }

    /**
     * Gets a human-readable representation of a data-type this normalization processor works with.
     */
    abstract protected function getDataTypeString(): string;

    /**
     * Gets a human-readable representation in plural of a data-type this normalization processor works with.
     */
    abstract protected function getDataTypePluralString(): string;

    /**
     * Adds to the context a regular expression that can be used to validate a value
     * of a data-type this processor works with.
     */
    protected function processRequirement(NormalizeValueContext $context): void
    {
        if ($context->isArrayAllowed()) {
            $requirement = $this->getArrayRequirement($context->getArrayDelimiter());
        } else {
            $requirement = $this->getRequirement();
        }
        if ($context->isRangeAllowed()) {
            $requirement = sprintf(
                '%s|%s',
                $requirement,
                $this->getRangeRequirement($context->getRangeDelimiter())
            );
        }
        $context->setRequirement($requirement);
    }

    /**
     * Gets a requirement for a single value.
     */
    abstract protected function getRequirement(): string;

    /**
     * Gets a requirement for a list of values.
     */
    protected function getArrayRequirement(string $arrayDelimiter): string
    {
        return sprintf('%1$s(%2$s%1$s)*', $this->getRequirement(), $arrayDelimiter);
    }

    /**
     * Gets a requirement for a pair of "from" and "to" values.
     */
    protected function getRangeRequirement(string $rangeDelimiter): string
    {
        return sprintf('%1$s%2$s%1$s', $this->getRequirement(), $rangeDelimiter);
    }

    /**
     * Does a value normalization (conversion to a concrete data-type) if needed.
     */
    protected function processNormalization(NormalizeValueContext $context): void
    {
        $value = $context->getResult();
        if (null !== $value && $this->isValueNormalizationRequired($value)) {
            if ($context->isRangeAllowed() && str_contains($value, $context->getRangeDelimiter())) {
                $context->setResult($this->normalizeRangeValue($value, $context->getRangeDelimiter()));
            } elseif ($context->isArrayAllowed()) {
                $context->setResult($this->normalizeArrayValue($value, $context->getArrayDelimiter()));
            } else {
                $this->validateValue($value);
                $context->setResult($this->normalizeValue($value));
            }
        }
        $context->setProcessed(true);
    }

    /**
     * Checks whether the given value need to be converted to a data-type this processor works with.
     */
    protected function isValueNormalizationRequired(mixed $value): bool
    {
        return \is_string($value);
    }

    protected function normalizeArrayValue(string $value, string $arrayDelimiter): mixed
    {
        $values = explode($arrayDelimiter, $value);
        try {
            $normalizedValue = $this->normalizeValues($values);
        } catch (\Exception $e) {
            if (\count($values) === 1) {
                throw $e;
            }
            throw new \UnexpectedValueException(
                sprintf('Expected an array of %s. Given "%s".', $this->getDataTypePluralString(), $value),
                0,
                $e
            );
        }

        if (\count($normalizedValue) === 1) {
            $normalizedValue = reset($normalizedValue);
        }

        return $normalizedValue;
    }

    protected function normalizeRangeValue(string $value, string $rangeDelimiter): Range
    {
        $delimiterPos = strpos($value, $rangeDelimiter);
        $values = [
            substr($value, 0, $delimiterPos),
            substr($value, $delimiterPos + \strlen($rangeDelimiter))
        ];
        try {
            $normalizedValues = $this->normalizeValues($values);
        } catch (\Exception $e) {
            throw new \UnexpectedValueException(
                sprintf(
                    'Expected a pair of %1$s (%3$s%4$s%3$s). Given "%2$s".',
                    $this->getDataTypePluralString(),
                    $value,
                    $this->getDataTypeString(),
                    $rangeDelimiter
                ),
                0,
                $e
            );
        }

        return new Range($normalizedValues[0], $normalizedValues[1]);
    }

    abstract protected function normalizeValue(mixed $value): mixed;

    protected function validateValue(string $value): void
    {
        if (!preg_match('/^' . $this->getRequirement() . '$/', $value)) {
            throw new \UnexpectedValueException(sprintf(
                'Expected %s value. Given "%s".',
                $this->getDataTypeString(),
                $value
            ));
        }
    }

    protected function normalizeValues(array $values): array
    {
        $normalizedValues = [];
        foreach ($values as $key => $val) {
            if ($this->isValueNormalizationRequired($val)) {
                $this->validateValue($val);
                $val = $this->normalizeValue($val);
            }
            $normalizedValues[$key] = $val;
        }

        return $normalizedValues;
    }
}
