<?php

namespace Oro\Bundle\CurrencyBundle\Test\Functional;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;

/**
 * This trait can be used in functional tests when you need to round price values.
 * It is expected that this trait will be used in classes that have "getContainer" static method.
 * E.g. classes derived from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.
 */
trait RoundPriceExtension
{
    /**
     * Returns the rounded price value.
     *
     * @param float|int|null $value
     *
     * @return float|int|null
     */
    protected function roundPrice($value)
    {
        if (null === $value) {
            return null;
        }

        /** @var RoundingServiceInterface $roundingService */
        $roundingService = self::getContainer()->get('oro_currency.tests.price_rounding_service');

        return $roundingService->round($value);
    }
}
