<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

class ExcelDateTimeTypeFormatter extends DateTimeTypeFormatter
{
    const FORMATTER_ALIAS = 'excel_datetime';

    /**
     * {@inheritdoc}
     */
    public function getPattern(
        $dateType = \IntlDateFormatter::SHORT,
        $timeType = \IntlDateFormatter::SHORT,
        $locale = null
    ) {
        $localeFormatter = $this->getIntlFormatter($dateType, $timeType, $locale);
        $pattern         = $localeFormatter->getPattern();

        return $this->modifyPattern($pattern, $dateType, $timeType);
    }

    public function getIntlFormatter(
        $dateType = \IntlDateFormatter::SHORT,
        $timeType = \IntlDateFormatter::SHORT,
        $timezone = null,
        $locale = null
    ) {
        $dateType = ($dateType == \IntlDateFormatter::NONE) ? \IntlDateFormatter::NONE : \IntlDateFormatter::SHORT;
        $timeType = ($timeType == \IntlDateFormatter::NONE) ? \IntlDateFormatter::NONE : \IntlDateFormatter::SHORT;

        if (!$timezone) {
            $timezone = $this->localeSettings->getTimeZone();
        }

        if (!$locale) {
            $locale = $this->localeSettings->getLocale();
        }

        return new \IntlDateFormatter(
            $locale,
            $dateType,
            $timeType,
            $timezone,
            \IntlDateFormatter::GREGORIAN
        );
    }

    /**
     * @param int $timestamp
     *
     * @return \DateTime
     */
    public function getDateTimeFromTimestamp($timestamp)
    {
        return new \DateTime(sprintf('@%f', $timestamp));
    }

    /**
     * Modify locale specific pattern to excel supported.
     * See icu formats:
     * @link http://userguide.icu-project.org/formatparse/datetime
     *
     * @param string $pattern
     * @param int    $timeType Constant IntlDateFormatter (NONE, FULL, LONG, MEDIUM, SHORT) or it's string name
     * @param int    $dateType Constant IntlDateFormatter (NONE, FULL, LONG, MEDIUM, SHORT) or it's string name
     *
     * @return string
     */
    protected function modifyPattern($pattern, $dateType, $timeType)
    {
        $patternParts = [];

        if ($dateType !== \IntlDateFormatter::NONE) {
            $order       = $this->detectOrder($pattern);
            $delimiter   = $this->detectDelimiter($pattern);
            $datePattern = str_replace(['m', 'd'], ['MM', 'dd'], implode($delimiter, $order));

            $patternParts[] = $datePattern;
        }

        if ($timeType !== \IntlDateFormatter::NONE) {
            $patternParts[] = 'HH:mm:ss';
        }

        return implode(' ', $patternParts);
    }

    /**
     * Detects order of day, month and year from IntlDateFormatter pattern string.
     *
     * @param string $pattern
     *
     * @return array
     */
    protected function detectOrder($pattern)
    {
        $result = [];
        $orders = ['mdy', 'dmy', 'ymd', 'myd', 'dym', 'ydm'];
        foreach (str_split($pattern) as $char) {
            if (!in_array($char, ['d', 'M', 'y'], true) || in_array($char, $result, true)) {
                continue;
            }
            $result[] = $char;
        }
        $result = array_map('strtolower', $result);
        $order  = implode('', $result);

        return in_array($order, $orders, true) ? $result : ['d', 'm', 'y'];
    }

    /**
     * @param string $pattern
     *
     * @return string
     */
    protected function detectDelimiter($pattern)
    {
        $dayMonthDelimiters = ['/', '.'];
        foreach ($dayMonthDelimiters as $delimiter) {
            if (strpos($pattern, $delimiter) !== false) {
                return $delimiter;
            }
        }

        return '/';
    }
}
