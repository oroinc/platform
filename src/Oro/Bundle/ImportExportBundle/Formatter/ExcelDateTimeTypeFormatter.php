<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

class ExcelDateTimeTypeFormatter extends DateTimeTypeFormatter implements DateTimeTypeConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertToDateTime($value, $type)
    {
        switch ($type) {
            case self::TYPE_DATETIME:
                return $this->convert($value);
            case self::TYPE_DATE:
                // Date data does not contain time and timezone information.
                return $this->convertToDate($value, null, null, 'UTC');
            case self::TYPE_TIME:
                // Time data does not contain date and timezone information.
                return $this->convertToTime($value, null, null, 'UTC');
            default:
                throw new InvalidArgumentException(sprintf('Couldn\'t parse "%s" type', $type));
        }
    }

    /**
     * Parse data string and convert to the \DateTime object.
     *
     * @param string          $value
     * @param string|int|null $dateType
     * @param string|int|null $timeType
     * @param string|null     $locale
     * @param string|null     $timeZone
     * @param string|null     $pattern
     *
     * @return \DateTime|false
     */
    public function convert(
        $value,
        $dateType = null,
        $timeType = null,
        $locale = null,
        $timeZone = null,
        $pattern = null
    ) {
        if (!$locale) {
            $locale = $this->localeSettings->getLocale();
        }

        if (!$timeZone) {
            $timeZone = $this->localeSettings->getTimeZone();
        }

        if ($value) {
            $formatter = $this->getFormatter($dateType, $timeType, $locale, $timeZone, $pattern, $value);
            $timestamp = $formatter->parse($value);
            if (intl_get_error_code() === 0) {
                return $this->getDateTime($timestamp);
            }
        }

        return false;
    }

    /**
     * Parse data string and convert to the \DateTime object.
     *
     * @param string          $value
     * @param string|int|null $dateType
     * @param string|null     $locale
     * @param string|null     $timezone
     *
     * @return \DateTime|false
     */
    public function convertToDate($value, $dateType = null, $locale = null, $timezone = null)
    {
        return $this->convert($value, $dateType, \IntlDateFormatter::NONE, $locale, $timezone);
    }

    /**
     * Parse data string and convert to the \DateTime object.
     *
     * @param string          $value
     * @param string|int|null $timeType
     * @param string|null     $locale
     * @param string|null     $timezone
     *
     * @return \DateTime|false
     */
    public function convertToTime($value, $timeType = null, $locale = null, $timezone = null)
    {
        return $this->convert($value, \IntlDateFormatter::NONE, $timeType, $locale, $timezone);
    }

    /**
     * {@inheritdoc}
     */
    public function getPattern($dateType, $timeType, $locale = null, $value = null)
    {
        $pattern = parent::getPattern($dateType, $timeType, $locale);

        return $this->modifyPattern($pattern, $dateType, $timeType, $value);
    }

    /**
     * Added leading zeros for 'day' and 'month'. Set time format to 'HH:mm:ss'.
     * See icu formats:
     *
     * @link http://userguide.icu-project.org/formatparse/datetime
     *
     * @param string      $pattern
     * @param int         $dateType Constant IntlDateFormatter (NONE, FULL, LONG, MEDIUM, SHORT) or it's string name
     *
     * @param int         $timeType Constant IntlDateFormatter (NONE, FULL, LONG, MEDIUM, SHORT) or it's string name
     * @param string|null $value
     *
     * @return string
     */
    protected function modifyPattern($pattern, $dateType, $timeType, $value = null)
    {
        $patternParts = [];

        if ($dateType !== \IntlDateFormatter::NONE) {
            $order       = $this->detectOrder($pattern);
            $delimiter   = $this->detectDelimiter($pattern);
            $datePattern = str_replace(['m', 'd'], ['MM', 'dd'], implode($delimiter, $order));

            $patternParts[] = $datePattern;
        }

        if ($timeType !== \IntlDateFormatter::NONE) {
            $timePattern = 'HH:mm:ss';
            if ($value !== null && substr_count($value, ':') < 2) {
                $timePattern = 'HH:mm';
            }
            $patternParts[] = $timePattern;
        }

        return implode(' ', $patternParts);
    }

    /**
     * Detects order of day, month and year in the IntlDateFormatter pattern string.
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
