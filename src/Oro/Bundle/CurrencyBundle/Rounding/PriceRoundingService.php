<?php

namespace Oro\Bundle\CurrencyBundle\Rounding;

use Oro\DBAL\Types\MoneyType;

/**
 * Provides price-specific rounding functionality with fixed precision and rounding mode.
 *
 * This service implements rounding for price values using a fixed precision of 2 decimal
 * places and the `ROUND_HALF_UP` rounding mode. It extends the base rounding service to
 * provide consistent price rounding behavior across the application, ensuring that all
 * monetary values are rounded uniformly according to standard financial practices.
 */
class PriceRoundingService extends AbstractRoundingService
{
    const FALLBACK_PRECISION = MoneyType::TYPE_SCALE;
    const DEFAULT_ROUND_TYPE = RoundingServiceInterface::ROUND_HALF_UP;
    const DEFAULT_PRECISION  = 2;

    #[\Override]
    public function getRoundType()
    {
        return self::DEFAULT_ROUND_TYPE;
    }

    #[\Override]
    public function getPrecision()
    {
        return self::DEFAULT_PRECISION;
    }
}
