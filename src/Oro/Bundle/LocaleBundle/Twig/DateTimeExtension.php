<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters to format date and time using either the supplied or the default localization
 * and timezone settings from the system configuration:
 *   - oro_format_datetime
 *   - oro_format_date
 *   - oro_format_day
 *   - oro_format_time
 */
class DateTimeExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;
    private ?DateTimeFormatterInterface $dateTimeFormatter = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param \DateTime|null|string $date
     * @return string
     */
    public function dateTimeMediumFormat($date)
    {
        if ($date instanceof \DateTime) {
            $date = $this->formatDateTime($date, ['timeType' => \IntlDateFormatter::MEDIUM]);
        }

        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('oro_format_datetime', [$this, 'formatDateTime']),
            new TwigFilter('oro_format_date', [$this, 'formatDate']),
            new TwigFilter('oro_format_day', [$this, 'formatDay']),
            new TwigFilter('oro_format_time', [$this, 'formatTime']),
        ];
    }

    /**
     * Formats date time according to locale settings.
     *
     * To format datetime given from `Date` or `Time` sql types, you can omit `timeZone` option,
     * e.g. `formatDateTime($date)`
     * To format the date and time of `DateTime` sql type value, you have to specify timeZone directly,
     * e.g. `formatDateTime($date, ['timeZone' => 'UTC']`
     *
     * Options format:
     * array(
     *     'dateType' => <dateType>,
     *     'timeType' => <timeType>,
     *     'locale' => <locale>,
     *     'timezone' => <timezone>,
     * )
     *
     * @param \DateTime|string|int $date
     * @param array                $options
     *
     * @return string
     */
    public function formatDateTime($date, array $options = [])
    {
        return $this->getDateTimeFormatter()->format(
            $date,
            $options['dateType'] ?? null,
            $options['timeType'] ?? null,
            $options['locale'] ?? null,
            $options['timeZone'] ?? null
        );
    }

    /**
     * Formats date time according to locale settings.
     *
     * To format date given from `Date` sql type, you can omit `timeZone` option,
     * e.g. `formatDate($date)`
     * To format the date part of `DateTime` sql type value, you have to specify timeZone directly,
     * e.g. `formatDate($date, ['timeZone' => 'UTC']`
     *
     * Options format:
     * array(
     *     'dateType' => <dateType>,
     *     'locale' => <locale>,
     *     'timeZone' => <timeZone>,
     * )
     *
     * @param \DateTime|string|int $date
     * @param array                $options
     *
     * @return string
     */
    public function formatDate($date, array $options = [])
    {
        return $this->getDateTimeFormatter()->formatDate(
            $date,
            $options['dateType'] ?? null,
            $options['locale'] ?? null,
            $options['timeZone'] ?? 'UTC'
        );
    }

    /**
     * Formats date according to locale settings.
     *
     * Options format:
     * array(
     *     'locale' => <locale>,
     *     'timeZone' => <timeZone>,
     * )
     *
     * @param \DateTime|string|int $date
     * @param array                $options
     *
     * @return string
     */
    public function formatDay($date, array $options = [])
    {
        return $this->getDateTimeFormatter()->formatDay(
            $date,
            $options['dateType'] ?? null,
            $options['locale'] ?? null,
            $options['timeZone'] ?? 'UTC'
        );
    }

    /**
     * Formats date time according to locale settings.
     *
     * To format time given from `Time` sql type, you can omit `timeZone` option,
     * e.g. `formatTime($date)`
     * To format the time part of `DateTime` sql type value, you have to specify timeZone directly,
     * e.g. `formatTime($date, ['timeZone' => 'UTC']`
     *
     * Options format:
     * array(
     *     'timeType' => <timeType>,
     *     'locale' => <locale>,
     *     'timeZone' => <timeZone>,
     * )
     *
     * @param \DateTime|string|int $date
     * @param array                $options
     *
     * @return string
     */
    public function formatTime($date, array $options = [])
    {
        return $this->getDateTimeFormatter()->formatTime(
            $date,
            $options['timeType'] ?? null,
            $options['locale'] ?? null,
            $options['timeZone'] ?? 'UTC'
        );
    }

    /**
     * {@inheritdoc]
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_locale.formatter.date_time' => DateTimeFormatterInterface::class,
        ];
    }

    protected function getDateTimeFormatter(): DateTimeFormatterInterface
    {
        if (null === $this->dateTimeFormatter) {
            $this->dateTimeFormatter = $this->container->get('oro_locale.formatter.date_time');
        }

        return $this->dateTimeFormatter;
    }
}
