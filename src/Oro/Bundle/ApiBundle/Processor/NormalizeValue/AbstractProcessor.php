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
    public function process(ContextInterface $context)
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
     *
     * @return string
     */
    abstract protected function getDataTypeString();

    /**
     * Gets a human-readable representation in plural of a data-type this normalization processor works with.
     *
     * @return string
     */
    abstract protected function getDataTypePluralString();

    /**
     * Adds to the context a regular expression that can be used to validate a value
     * of a data-type this processor works with.
     *
     * @param NormalizeValueContext $context
     */
    public function processRequirement(NormalizeValueContext $context)
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
     *
     * @return string
     */
    abstract protected function getRequirement();

    /**
     * Gets a requirement for a list of values.
     *
     * @param string $arrayDelimiter
     *
     * @return string
     */
    protected function getArrayRequirement($arrayDelimiter)
    {
        return sprintf('%1$s(%2$s%1$s)*', $this->getRequirement(), $arrayDelimiter);
    }

    /**
     * Gets a requirement for a pair of "from" and "to" values.
     *
     * @param string $rangeDelimiter
     *
     * @return string
     */
    protected function getRangeRequirement($rangeDelimiter)
    {
        return sprintf('%1$s%2$s%1$s', $this->getRequirement(), $rangeDelimiter);
    }

    /**
     * Does a value normalization (conversion to a concrete data-type) if needed.
     *
     * @param NormalizeValueContext $context
     */
    protected function processNormalization(NormalizeValueContext $context)
    {
        $value = $context->getResult();
        if (null !== $value && $this->isValueNormalizationRequired($value)) {
            if ($context->isRangeAllowed() && false !== strpos($value, $context->getRangeDelimiter())) {
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
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function isValueNormalizationRequired($value)
    {
        return is_string($value);
    }

    /**
     * @param string $value
     * @param string $arrayDelimiter
     *
     * @return mixed
     * @throws \Exception
     */
    protected function normalizeArrayValue($value, $arrayDelimiter)
    {
        $values = explode($arrayDelimiter, $value);
        try {
            $normalizedValue = $this->normalizeValues($values);
        } catch (\Exception $e) {
            if (count($values) === 1) {
                throw $e;
            } else {
                throw new \UnexpectedValueException(
                    sprintf('Expected an array of %s. Given "%s".', $this->getDataTypePluralString(), $value),
                    0,
                    $e
                );
            }
        }

        if (count($normalizedValue) === 1) {
            $normalizedValue = reset($normalizedValue);
        }

        return $normalizedValue;
    }

    /**
     * @param string $value
     * @param string $rangeDelimiter
     *
     * @return Range
     * @throws \Exception
     */
    protected function normalizeRangeValue($value, $rangeDelimiter)
    {
        $delimiterPos = strpos($value, $rangeDelimiter);
        $values = [
            substr($value, 0, $delimiterPos),
            substr($value, $delimiterPos + strlen($rangeDelimiter))
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

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    abstract protected function normalizeValue($value);

    /**
     * @param string $value
     */
    protected function validateValue($value)
    {
        if (!preg_match('/^' . $this->getRequirement() . '$/', $value)) {
            throw new \UnexpectedValueException(
                sprintf('Expected %s value. Given "%s".', $this->getDataTypeString(), $value)
            );
        }
    }

    /**
     * @param array $values
     *
     * @return array
     */
    protected function normalizeValues(array $values)
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
