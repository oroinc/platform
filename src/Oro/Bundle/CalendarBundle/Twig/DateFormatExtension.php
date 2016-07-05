<?php

namespace Oro\Bundle\CalendarBundle\Twig;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class DateFormatExtension extends \Twig_Extension
{
    /** @var DateTimeFormatter */
    protected $formatter;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param DateTimeFormatter $formatter
     */
    public function __construct(DateTimeFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'calendar_date_range' => new \Twig_Function_Method(
                $this,
                'formatCalendarDateRange'
            ),
            'calendar_date_range_user' => new \Twig_Function_Method(
                $this,
                'formatCalendarDateRangeUser'
            )
        ];
    }

    /**
     * Returns a string represents a range between $startDate and $endDate, formatted according the given parameters
     * Examples:
     *      $endDate is not specified
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:30pm - when $skipTime = false
     *      $startDate equals to $endDate
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:30pm - when $skipTime = false
     *      $startDate and $endDate are the same day
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:00pm – 5:30pm - when $skipTime = false
     *      $startDate and $endDate are different days
     *          Thu Oct 17, 2013 5:00pm – Thu Oct 18, 2013 5:00pm - when $skipTime = false
     *          Thu Oct 17, 2013 – Thu Oct 18, 2013 - when $skipTime = true
     *
     * @param \DateTime|null    $startDate
     * @param \DateTime|null    $endDate
     * @param bool              $skipTime
     * @param string|int|null   $dateType \IntlDateFormatter constant or it's string name
     * @param string|int|null   $timeType \IntlDateFormatter constant or it's string name
     * @param string|null       $locale
     * @param string|null       $timeZone
     *
     * @return string
     */
    public function formatCalendarDateRange(
        \DateTime $startDate = null,
        \DateTime $endDate = null,
        $skipTime = false,
        $dateType = null,
        $timeType = null,
        $locale = null,
        $timeZone = null
    ) {
        if (is_null($startDate)) {
            // exit because nothing to format.
            // We have to accept null as $startDate because the validator of email templates calls functions
            // with empty arguments
            return '';
        }

        // check if $endDate is not specified or $startDate equals to $endDate
        if (is_null($endDate) || $startDate == $endDate) {
            return $skipTime
                ? $this->formatter->formatDate($startDate, $dateType, $locale, $timeZone)
                : $this->formatter->format($startDate, $dateType, $timeType, $locale, $timeZone);
        }

        // check if $startDate and $endDate are the same day
        if ($startDate->format('Ymd') == $endDate->format('Ymd')) {
            if ($skipTime) {
                return $this->formatter->formatDate($startDate, $dateType, $locale, $timeZone);
            }

            return sprintf(
                '%s %s - %s',
                $this->formatter->formatDate($startDate, $dateType, $locale, $timeZone),
                $this->formatter->formatTime($startDate, $timeType, $locale, $timeZone),
                $this->formatter->formatTime($endDate, $timeType, $locale, $timeZone)
            );
        }

        // $startDate and $endDate are different days
        if ($skipTime) {
            return sprintf(
                '%s - %s',
                $this->formatter->formatDate($startDate, $dateType, $locale, $timeZone),
                $this->formatter->formatDate($endDate, $dateType, $locale, $timeZone)
            );
        }

        return sprintf(
            '%s - %s',
            $this->formatter->format($startDate, $dateType, $timeType, $locale, $timeZone),
            $this->formatter->format($endDate, $dateType, $timeType, $locale, $timeZone)
        );
    }

    /**
     * Returns a string represents a range between $startDate and $endDate, formatted according the given parameters
     * Examples:
     *      $endDate is not specified
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:30pm - when $skipTime = false
     *      $startDate equals to $endDate
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:30pm - when $skipTime = false
     *      $startDate and $endDate are the same day
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:00pm – 5:30pm - when $skipTime = false
     *      $startDate and $endDate are different days
     *          Thu Oct 17, 2013 5:00pm – Thu Oct 18, 2013 5:00pm - when $skipTime = false
     *          Thu Oct 17, 2013 – Thu Oct 18, 2013 - when $skipTime = true
     *
     * @param \DateTime|null    $startDate
     * @param \DateTime|null    $endDate
     * @param bool              $skipTime
     * @param string|int|null   $dateType \IntlDateFormatter constant or it's string name
     * @param string|int|null   $timeType \IntlDateFormatter constant or it's string name
     * @param string|null       $locale
     * @param string|null       $timeZone
     * @param int|null          $user
     *
     * @return string
     */
    public function formatCalendarDateRangeUser(
        \DateTime $startDate = null,
        \DateTime $endDate = null,
        $skipTime = false,
        $dateType = null,
        $timeType = null,
        $locale = null,
        $timeZone = null,
        $user = null
    ) {
        // Get localization settings from user scope
        if ($user) {
            $locale = $this->configManager->get('oro_locale.locale');
            $timeZone = $this->configManager->get('oro_locale.timezone');
        }

        return $this->formatCalendarDateRange(
            $startDate,
            $endDate,
            $skipTime,
            $dateType,
            $timeType,
            $locale,
            $timeZone
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar';
    }
}
