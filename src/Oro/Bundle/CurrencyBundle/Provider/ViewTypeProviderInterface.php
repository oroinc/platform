<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

/**
 * Defines the contract for providers that supply currency display format preferences.
 *
 * Implement this interface to create providers that determine how currencies should
 * be displayed in the user interface. The view type controls whether currencies are
 * shown as symbols ($, €), ISO codes (USD, EUR), names (Dollar, Euro), or full names
 * (US Dollar, Euro).
 */
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
