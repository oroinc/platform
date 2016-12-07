<?php

namespace Oro\Bundle\CurrencyBundle\Rounding;

class PriceRoundingService extends AbstractRoundingService
{
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
