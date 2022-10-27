<?php

namespace Oro\Bundle\CurrencyBundle\Rounding;

use Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException;

/**
 * Represents a service that can perform number rounding.
 */
interface RoundingServiceInterface
{
    const ROUND_CEILING = \NumberFormatter::ROUND_CEILING;
    const ROUND_FLOOR = \NumberFormatter::ROUND_FLOOR;
    const ROUND_DOWN = \NumberFormatter::ROUND_DOWN;
    const ROUND_UP = \NumberFormatter::ROUND_UP;
    const ROUND_HALF_EVEN = \NumberFormatter::ROUND_HALFEVEN;
    const ROUND_HALF_DOWN = \NumberFormatter::ROUND_HALFDOWN;
    const ROUND_HALF_UP = \NumberFormatter::ROUND_HALFUP;

    /**
     * @param float|int $value
     * @param int $precision
     * @param int|null $roundType
     * @return float|int
     * @throws InvalidRoundingTypeException
     */
    public function round($value, $precision = null, $roundType = null);

    /**
     * Returns default precision configured for service
     *
     * @return int
     */
    public function getPrecision();

    /**
     * Returns default rounding type configured for service
     * Should be compatible with Intl default mods
     *
     * @return int
     */
    public function getRoundType();
}
