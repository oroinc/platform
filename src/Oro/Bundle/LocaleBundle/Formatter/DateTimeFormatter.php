<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Format dates based on locale settings
 */
class DateTimeFormatter implements DateTimeFormatterInterface
{
    const DEFAULT_DATE_TYPE = \IntlDateFormatter::MEDIUM;
    const DEFAULT_TIME_TYPE = \IntlDateFormatter::SHORT;

    /** @var LocaleSettings */
    protected $localeSettings;

    /** @var TranslatorInterface */
    private $translator;

    /** @var \IntlDateFormatter[] */
    protected $cachedFormatters = [];

    /** @var string[] */
    protected $cachedPatterns = [];

    public function __construct(LocaleSettings $localeSettings, TranslatorInterface $translator)
    {
        $this->localeSettings = $localeSettings;
        $this->translator     = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function format($date, $dateType = null, $timeType = null, $locale = null, $timeZone = null, $pattern = null)
    {
        if (!$timeZone) {
            $timeZone = $this->localeSettings->getTimeZone();
        }
        $dateTime = $this->getDateTime($date);

        // use Formatter if we have DateTime object and return the incoming argument otherwise
        if ($dateTime) {
            if (!$pattern) {
                $pattern = $this->getPattern($dateType, $timeType, $locale);
            }
            $formatter = $this->getFormatter($dateType, $timeType, $locale, $timeZone, $pattern);

            return $formatter->format((int)$dateTime->format('U'));
        }
        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function formatDate($date, $dateType = null, $locale = null, $timeZone = null)
    {
        return $this->format($date, $dateType, \IntlDateFormatter::NONE, $locale, $timeZone);
    }

    /**
     * {@inheritdoc}
     */
    public function formatYear($date, $dateType = null, $locale = null, $timeZone = null)
    {
        $pattern = $this->translator->trans('oro.locale.date_format.year', [], null, $locale);

        return $this->format($date, $dateType, \IntlDateFormatter::NONE, $locale, $timeZone, $pattern);
    }

    /**
     * {@inheritdoc}
     */
    public function formatQuarter($date, $dateType = null, $locale = null, $timeZone = null)
    {
        $pattern = $this->translator->trans('oro.locale.date_format.quarter', [], null, $locale);

        return $this->format($date, $dateType, \IntlDateFormatter::NONE, $locale, $timeZone, $pattern);
    }

    /**
     * {@inheritdoc}
     */
    public function formatMonth($date, $dateType = null, $locale = null, $timeZone = null)
    {
        $pattern = $this->translator->trans('oro.locale.date_format.month', [], null, $locale);

        return $this->format($date, $dateType, \IntlDateFormatter::NONE, $locale, $timeZone, $pattern);
    }

    /**
     * {@inheritdoc}
     */
    public function formatDay($date, $dateType = null, $locale = null, $timeZone = null)
    {
        $pattern = $this->translator->trans('oro.locale.date_format.day', [], null, $locale);

        return $this->format($date, $dateType, \IntlDateFormatter::NONE, $locale, $timeZone, $pattern);
    }

    /**
     * {@inheritdoc}
     */
    public function formatTime($date, $timeType = null, $locale = null, $timeZone = null)
    {
        return $this->format($date, \IntlDateFormatter::NONE, $timeType, $locale, $timeZone);
    }

    /**
     * {@inheritdoc}
     */
    public function getPattern($dateType, $timeType, $locale = null, $value = null)
    {
        return $this->updatePattern($dateType, $timeType, $locale);
    }

    /**
     * Update cached pattern
     *
     * @param int|string  $dateType Constant of IntlDateFormatter (NONE, FULL, LONG, MEDIUM, SHORT) or it's string name
     * @param int|string  $timeType Constant IntlDateFormatter (NONE, FULL, LONG, MEDIUM, SHORT) or it's string name
     * @param string|null $locale
     * @param string|null $pattern
     * @return string
     */
    public function updatePattern($dateType, $timeType, $locale = null, $pattern = null): string
    {
        if (!$locale) {
            $locale = $this->localeSettings->getLocale();
        }

        if (null === $dateType) {
            $dateType = static::DEFAULT_DATE_TYPE;
        }

        if (null === $timeType) {
            $timeType = static::DEFAULT_TIME_TYPE;
        }

        $dateType = $this->parseDateType($dateType);
        $timeType = $this->parseDateType($timeType);

        $key = md5(serialize([$dateType, $timeType, $locale]));
        if ($pattern) {
            $this->cachedPatterns[$key] = $pattern;
        }

        if (!isset($this->cachedPatterns[$key])) {
            $intlFormatter = new \IntlDateFormatter($locale, $dateType, $timeType, null, \IntlDateFormatter::GREGORIAN);
            $this->cachedPatterns[$key] = $intlFormatter->getPattern();
        }

        return $this->cachedPatterns[$key];
    }

    /**
     * Gets instance of intl date formatter by parameters
     *
     * @param string|int|null $dateType
     * @param string|int|null $timeType
     * @param string|null     $locale
     * @param string|null     $timeZone
     * @param string|null     $pattern
     * @param string|null     $value
     *
     * @return \IntlDateFormatter
     */
    protected function getFormatter($dateType, $timeType, $locale, $timeZone, $pattern, $value = null)
    {
        if (!$pattern) {
            $pattern = $this->getPattern($dateType, $timeType, $locale, $value);
        }

        $key = md5(serialize([$timeZone, $pattern]));
        if (!isset($this->cachedFormatters[$key])) {
            $this->cachedFormatters[$key] = new \IntlDateFormatter(
                $this->localeSettings->getLanguage(),
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                $timeZone,
                \IntlDateFormatter::GREGORIAN,
                $pattern
            );
        }

        return $this->cachedFormatters[$key];
    }

    /**
     * Try to parse date type. If null return \IntlDateFormatter::FULL type, if string try to eval
     * constant with this name.
     *
     * @param int|string|null $dateType A constant of \IntlDateFormatter type, a string name of type or null
     * @return int
     * @throws \InvalidArgumentException
     */
    protected function parseDateType($dateType)
    {
        if (null === $dateType) {
            $dateType = \IntlDateFormatter::MEDIUM;
        } elseif (!is_int($dateType) && is_string($dateType)) {
            $dateConstant = 'IntlDateFormatter::' . strtoupper($dateType);
            if (defined($dateConstant)) {
                $dateType = constant($dateConstant);
            } else {
                throw new \InvalidArgumentException("IntlDateFormatter has no type '$dateType'");
            }
        }

        $allowedTypes = array(
            \IntlDateFormatter::FULL, \IntlDateFormatter::LONG,
            \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE
        );

        if (!in_array((int)$dateType, $allowedTypes)) {
            throw new \InvalidArgumentException("IntlDateFormatter type '$dateType' is not supported");
        }

        return (int)$dateType;
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($date)
    {
        if ($date === false || $date === null) {
            return false;
        }

        if ($date instanceof \DateTimeInterface) {
            return $date;
        }

        $defaultTimezone = date_default_timezone_get();

        date_default_timezone_set('UTC');

        if (is_numeric($date)) {
            $date = (int)$date;
        }

        if (is_string($date)) {
            $date = strtotime($date);
        }

        $result = new \DateTime();
        $result->setTimestamp($date);

        date_default_timezone_set($defaultTimezone);

        return $result;
    }
}
