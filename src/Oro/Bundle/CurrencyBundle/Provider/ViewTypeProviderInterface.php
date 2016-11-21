<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

interface ViewTypeProviderInterface
{
    const VIEW_TYPE_SYMBOL    = 'symbol';
    const VIEW_TYPE_ISO_CODE  = 'iso_code';
    const VIEW_TYPE_NAME      = 'name';
    const VIEW_TYPE_FULL_NAME = 'full_name';

    /**
     * @return string one of the constants of this interface
     */
    public function getViewType();
}
