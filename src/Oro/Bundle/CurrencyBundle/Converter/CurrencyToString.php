<?php

namespace Oro\Bundle\CurrencyBundle\Converter;

/**
 * Used in datagrid for formatting currency fields value to string
 */
class CurrencyToString
{
    /**
     * @param string $moneyValue
     * @param string $currency
     *
     * @return string
     */
    public function convert($moneyValue, $currency)
    {
        if ($currency !== null && $moneyValue !== null) {
            return sprintf('%s%s', $currency, $moneyValue);
        }

        return '';
    }
}
