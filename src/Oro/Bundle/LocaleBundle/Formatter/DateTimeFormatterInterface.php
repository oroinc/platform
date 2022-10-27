<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

/**
 * Declare methods that must be defined in custom date formatters
 */
interface DateTimeFormatterInterface
{
    /**
     * Formats date time
     *
     * @param \DateTime|string|int $date
     * @param string|int|null $dateType
     * @param string|int|null $timeType
     * @param string|null $locale
     * @param string|null $timeZone
     * @param string|null $pattern
     * @return string
     */
    public function format(
        $date,
        $dateType = null,
        $timeType = null,
        $locale = null,
        $timeZone = null,
        $pattern = null
    );

    /**
     * Formats date without time
     *
     * @param \DateTime|string|int $date
     * @param string|int|null $dateType
     * @param string|null $locale
     * @param string|null $timeZone
     * @return string
     */
    public function formatDate($date, $dateType = null, $locale = null, $timeZone = null);

    /**
     * @param \DateTime|string|int $date
     * @param string|int|null      $dateType
     * @param string|null          $locale
     * @param string|null          $timeZone
     *
     * @return string
     */
    public function formatYear($date, $dateType = null, $locale = null, $timeZone = null);

    /**
     * @param \DateTime|string|int $date
     * @param string|int|null      $dateType
     * @param string|null          $locale
     * @param string|null          $timeZone
     *
     * @return string
     */
    public function formatQuarter($date, $dateType = null, $locale = null, $timeZone = null);

    /**
     * @param \DateTime|string|int $date
     * @param string|int|null      $dateType
     * @param string|null          $locale
     * @param string|null          $timeZone
     *
     * @return string
     */
    public function formatMonth($date, $dateType = null, $locale = null, $timeZone = null);

    /**
     * Formats day without time and year
     *
     * @param \DateTime|string|int $date
     * @param string|int|null $dateType
     * @param string|null $locale
     * @param string|null $timeZone
     * @return string
     */
    public function formatDay($date, $dateType = null, $locale = null, $timeZone = null);

    /**
     * Formats time without date
     *
     * @param \DateTime|string|int $date
     * @param string|int|null $timeType
     * @param string|null $locale
     * @param string|null $timeZone
     * @return string
     */
    public function formatTime($date, $timeType = null, $locale = null, $timeZone = null);

    /**
     * Get the pattern used for the IntlDateFormatter
     *
     * @param int|string  $dateType Constant of IntlDateFormatter (NONE, FULL, LONG, MEDIUM, SHORT) or it's string name
     * @param int|string  $timeType Constant IntlDateFormatter (NONE, FULL, LONG, MEDIUM, SHORT) or it's string name
     * @param string|null $locale
     * @param string|null $value
     *
     * @return string
     */
    public function getPattern($dateType, $timeType, $locale = null, $value = null);

    /**
     * Returns DateTime by $data and $timezone and false otherwise
     *
     * @param \DateTimeInterface|string|int $date
     *
     * @return \DateTimeInterface|false
     */
    public function getDateTime($date);
}
