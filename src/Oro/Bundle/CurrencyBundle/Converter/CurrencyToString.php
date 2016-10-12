<?php

namespace Oro\Bundle\CurrencyBundle\Converter;

/**
 * Used in datagrid for formatting currency fields value to string
 */
class CurrencyToString
{
    /**
     * @return callback
     */
    public static function getConverterCallback()
    {
        return function ($moneyValue, $currency) {
            if ($currency !== null && $moneyValue !== null) {
                return sprintf('%s%d', $currency, $moneyValue);
            } else {
                return '';
            }
        };
    }
}
