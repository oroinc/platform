<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DateTimeExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return DateTimeFormatter
     */
    protected function getDateTimeFormatter()
    {
        return $this->container->get('oro_locale.formatter.date_time');
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
            new \Twig_SimpleFilter(
                'oro_format_datetime',
                [$this, 'formatDateTime'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_date',
                [$this, 'formatDate'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_day',
                [$this, 'formatDay'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_time',
                [$this, 'formatTime'],
                ['is_safe' => ['html']]
            ),
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
        $dateType = $this->getOption($options, 'dateType');
        $timeType = $this->getOption($options, 'timeType');
        $locale = $this->getOption($options, 'locale');
        $timeZone = $this->getOption($options, 'timeZone');

        return $this->getDateTimeFormatter()->format($date, $dateType, $timeType, $locale, $timeZone);
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
        $dateType = $this->getOption($options, 'dateType');
        $locale = $this->getOption($options, 'locale');
        $timeZone = $this->getOption($options, 'timeZone', 'UTC');

        return $this->getDateTimeFormatter()->formatDate($date, $dateType, $locale, $timeZone);
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
        $dateType = $this->getOption($options, 'dateType');
        $locale = $this->getOption($options, 'locale');
        $timeZone = $this->getOption($options, 'timeZone', 'UTC');

        return $this->getDateTimeFormatter()->formatDay($date, $dateType, $locale, $timeZone);
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
        $timeType = $this->getOption($options, 'timeType');
        $locale = $this->getOption($options, 'locale');
        $timeZone = $this->getOption($options, 'timeZone', 'UTC');

        return $this->getDateTimeFormatter()->formatTime($date, $timeType, $locale, $timeZone);
    }

    /**
     * Gets option or default value if option not exist
     *
     * @param array  $options
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getOption(array $options, $name, $default = null)
    {
        return isset($options[$name]) ? $options[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_locale_datetime';
    }
}
