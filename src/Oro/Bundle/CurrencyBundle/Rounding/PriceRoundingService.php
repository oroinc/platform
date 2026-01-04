<?php

namespace Oro\Bundle\CurrencyBundle\Rounding;

use Oro\DBAL\Types\MoneyType;

class PriceRoundingService extends AbstractRoundingService
{
    public const FALLBACK_PRECISION = MoneyType::TYPE_SCALE;
    public const DEFAULT_ROUND_TYPE = RoundingServiceInterface::ROUND_HALF_UP;
    public const DEFAULT_PRECISION  = 2;

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
