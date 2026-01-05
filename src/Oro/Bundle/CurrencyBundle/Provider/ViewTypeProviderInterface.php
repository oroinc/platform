<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

interface ViewTypeProviderInterface
{
    public const VIEW_TYPE_SYMBOL    = 'symbol';
    public const VIEW_TYPE_ISO_CODE  = 'iso_code';
    public const VIEW_TYPE_NAME      = 'name';
    public const VIEW_TYPE_FULL_NAME = 'full_name';

    /**
     * @return string one of the constants of this interface
     */
    public function getViewType();
}
