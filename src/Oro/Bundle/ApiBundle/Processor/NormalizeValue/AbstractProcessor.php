<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

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
            $context->setRequirement($this->getArrayRequirement($context->getArrayDelimiter()));
        } else {
            $context->setRequirement($this->getRequirement());
        }
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
     * Does a value normalization (conversion to a concrete data-type) if needed.
     *
     * @param NormalizeValueContext $context
     */
    protected function processNormalization(NormalizeValueContext $context)
    {
        $value = $context->getResult();
        if (null !== $value && $this->isValueNormalizationRequired($value)) {
            if ($context->isArrayAllowed() && !is_array($value)) {
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
     */
    protected function normalizeArrayValue($value, $arrayDelimiter)
    {
        $normalizedValue = [];
        $values          = explode($arrayDelimiter, $value);
        foreach ($values as $val) {
            try {
                if ($this->isValueNormalizationRequired($val)) {
                    $this->validateValue($val);
                    $val = $this->normalizeValue($val);
                }
                $normalizedValue[] = $val;
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
        }

        return count($normalizedValue) === 1
            ? reset($normalizedValue)
            : $normalizedValue;
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
}
