<?php

namespace Oro\Bundle\CurrencyBundle\Config;

interface CurrencyConfigInterface
{
    const VIEW_TYPE_SYMBOL = 'symbol';
    const VIEW_TYPE_ISO_CODE = 'iso_code';

    /**
     * Returned currency list from system config
     * or empty array is system does't support
     * multi currency functionality
     *
     * @return string[]
     */
    public function getCurrencyList();

    /**
     * Returned default currency from system config
     *
     * @return string
     */
    public function getDefaultCurrency();

    /**
     * Returned view type for currency
     * from system config
     *
     * @return string
     */
    public function getViewType();
}
