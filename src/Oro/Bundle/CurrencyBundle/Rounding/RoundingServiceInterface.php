<?php

namespace Oro\Bundle\CurrencyBundle\Rounding;

use Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException;

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
     * @param float|integer $value
     * @param integer $precision
     * @param integer $roundType
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
