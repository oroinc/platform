<?php

namespace Oro\Bundle\CurrencyBundle\Rounding;

use Oro\DBAL\Types\MoneyType;

class PriceRoundingService extends AbstractRoundingService
{
    const FALLBACK_PRECISION = MoneyType::TYPE_SCALE;
    const DEFAULT_ROUND_TYPE = RoundingServiceInterface::ROUND_HALF_UP;
    const DEFAULT_PRECISION  = 2;

    /** {@inheritdoc} */
    public function getRoundType()
    {
        return self::DEFAULT_ROUND_TYPE;
    }

    /** {@inheritdoc} */
    public function getPrecision()
    {
        return self::DEFAULT_PRECISION;
    }
}
