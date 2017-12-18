<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\Extension\Core\DataTransformer\PercentToLocalizedStringTransformer as BaseTransformer;

/**
 * Fix for standard transformer that supports precision = null
 * and if the conversion type is "fractional", convert strings represent an integer value to float instead of integer.
 */
class PercentToLocalizedStringTransformer extends BaseTransformer
{
    /** @var int|null */
    private $precision;

    /** @var string|null */
    private $type;

    /**
     * {@inheritDoc}
     */
    public function __construct($precision = null, $type = null)
    {
        parent::__construct($precision, $type);
        $this->precision = $precision;
        $this->type = $type;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
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
    protected function getNumberFormatter()
    {
        $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);
        if (null !== $this->precision) {
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $this->precision);
        }

        return $formatter;
    }
}
