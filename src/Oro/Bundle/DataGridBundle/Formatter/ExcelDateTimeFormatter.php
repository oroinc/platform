<?php

namespace Oro\Bundle\DataGridBundle\Formatter;

class ExcelDateTimeFormatter extends LocaleDateTimeFormatter
{
    /**
     * {@inheritdoc}
     */
    public function getPattern($dateType, $timeType, $locale = null)
    {
        if (!$locale) {
            $locale = $this->localeSettings->getLocale();
        }

        if ($timeType !== \IntlDateFormatter::NONE) {
            $timeType = \IntlDateFormatter::SHORT;
        }

        $localeFormatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::SHORT,
            $timeType,
            null,
            \IntlDateFormatter::GREGORIAN
        );
        $pattern         = $localeFormatter->getPattern();

        return $this->modifyPattern($pattern, $timeType);
    }

    /**
     * @param string $pattern
     * @param int    $timeType
     * @return string
     */
    protected function modifyPattern($pattern, $timeType)
    {
        $order       = $this->detectOrder($pattern);
        $delimiter   = $this->detectDelimiter($pattern);
        $datePattern = str_replace(['m', 'd'], ['MM', 'dd'], implode($delimiter, $order));
        $timePattern = $timeType !== \IntlDateFormatter::NONE ? ' HH:mm:ss' : '';

        return $datePattern . $timePattern;
    }

    /**
     * @param string $pattern
     * @return array
     */
    protected function detectOrder($pattern)
    {
        $result = [];
        $orders = ['mdy', 'dmy', 'ymd', 'myd', 'dym', 'ydm'];
        foreach (str_split($pattern) as $char) {
            if (!in_array($char, ['d', 'M', 'y']) || in_array($char, $result)) {
                continue;
            }
            $result[] = $char;
        }
        $result = array_map('strtolower', $result);
        $order  = implode('', $result);

        return in_array($order, $orders) ? $result : ['d', 'm', 'y'];
    }

    /**
     * @param string $pattern
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
