<?php

namespace Oro\Bundle\CurrencyBundle\Config;

interface CurrenciesViewTypeAwareInterface
{
    const VIEW_TYPE_SYMBOL = 'symbol';
    const VIEW_TYPE_ISO_CODE = 'iso_code';

    /**
     * Returned view type for currency
     * from system config
     *
     * @return string
     */
    public function getViewType();
}
