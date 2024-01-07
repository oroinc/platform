<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

/**
 * Fix for standard transformer that supports precision = null
 * and if the conversion type is "fractional", convert strings represent an integer value to float instead of integer.
 */
class PercentToLocalizedStringTransformer extends Symfony54PercentToLocalizedStringTransformer
{
    /** @var int|null */
    private $scale;

    /** @var string|null */
    private $type;

    /**
     * {@inheritDoc}
     */
    public function __construct($scale = null, $type = null)
    {
        parent::__construct($scale, $type);
        $this->scale = $scale;
        $this->type = $type;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value): int|float|null
    {
        $result = parent::reverseTransform($value);
        if (is_int($result) && self::FRACTIONAL === $this->type) {
            $result = (float)$result;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNumberFormatter(): \NumberFormatter
    {
        $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);
        if (null !== $this->scale) {
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $this->scale);
            $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $this->scale);
        } elseif (self::FRACTIONAL === $this->type) {
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, \PHP_FLOAT_DIG);
        }

        return $formatter;
    }
}
