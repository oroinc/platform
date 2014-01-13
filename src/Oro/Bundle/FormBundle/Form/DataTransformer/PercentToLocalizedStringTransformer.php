<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\Extension\Core\DataTransformer\PercentToLocalizedStringTransformer as BaseTransformer;

/**
 * Fix for standard transformer that supports precision = null
 */
class PercentToLocalizedStringTransformer extends BaseTransformer
{
    /**
     * @var int|null
     */
    private $precision;

    /**
     * {@inheritDoc}
     */
    public function __construct($precision = null, $type = null)
    {
        parent::__construct($precision, $type);

        $this->precision = $precision;
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
