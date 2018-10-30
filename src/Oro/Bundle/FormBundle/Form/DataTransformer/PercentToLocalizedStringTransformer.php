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
        if (null !== $this->scale) {
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $this->scale);
            $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $this->scale);
        }

        return $formatter;
    }
}
