<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

/**
 * Defines the contract for comprehensive currency information providers.
 *
 * This interface combines the functionality of both currency list provision and
 * default currency provision. Implement this interface for providers that need to
 * supply both the list of available currencies and the system's default currency.
 */
interface CurrencyProviderInterface extends CurrencyListProviderInterface, DefaultCurrencyProviderInterface
{
}
