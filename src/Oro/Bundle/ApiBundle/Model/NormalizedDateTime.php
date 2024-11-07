<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * Representation of a normalized date and time value.
 */
class NormalizedDateTime extends \DateTime
{
    public const PRECISION_YEAR = 1;
    public const PRECISION_MONTH = 2;
    public const PRECISION_DAY = 3;
    public const PRECISION_HOUR = 4;
    public const PRECISION_MINUTE = 5;
    public const PRECISION_SECOND = 6;

    private int $precision = self::PRECISION_SECOND;

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function setPrecision(int $precision): void
    {
        $this->precision = $precision;
    }
}
