<?php

namespace Oro\Bundle\LocaleBundle\Converter;

/**
 * Defines the contract for converting date/time formats between different standards.
 *
 * Implementations of this interface are responsible for converting date and time format strings
 * from one standard (e.g., ICU format) to another (e.g., PHP date format, Moment.js format).
 * This is essential for bridging different formatting standards used across the application
 * and third-party libraries.
 */
interface DateTimeFormatConverterInterface
{
    /**
     * @param string|null $locale
     * @return string
     */
    public function getDayFormat($locale = null);

    /**
     * @param int|string|null $dateFormat \IntlDateFormatter format constant it's string name
     * @param string|null $locale
     * @return string
     */
    public function getDateFormat($dateFormat = null, $locale = null);

    /**
     * @param int|string|null $timeFormat \IntlDateFormatter format constant or it's string name
     * @param string|null $locale
     * @return string
     */
    public function getTimeFormat($timeFormat = null, $locale = null);

    /**
     * @param int|string|null $dateFormat \IntlDateFormatter format constant it's string name
     * @param int|string|null $timeFormat \IntlDateFormatter format constant it's string name
     * @param string|null $locale
     * @return string
     */
    public function getDateTimeFormat($dateFormat = null, $timeFormat = null, $locale = null);
}
