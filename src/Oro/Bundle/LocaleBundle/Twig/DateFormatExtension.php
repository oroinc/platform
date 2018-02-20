<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DateFormatExtension extends \Twig_Extension
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
     * @return DateTimeFormatConverterRegistry
     */
    protected function getDateTimeFormatConverterRegistry()
    {
        return $this->container->get('oro_locale.format_converter.date_time.registry');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_locale_dateformat';
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_datetime_formatter_list', [$this, 'getDateTimeFormatterList']),
            new \Twig_SimpleFunction('oro_day_format', [$this, 'getDayFormat']),
            new \Twig_SimpleFunction('oro_date_format', [$this, 'getDateFormat']),
            new \Twig_SimpleFunction('oro_time_format', [$this, 'getTimeFormat']),
            new \Twig_SimpleFunction('oro_datetime_format', [$this, 'getDateTimeFormat']),
        ];
    }

    /**
     * @return array
     */
    public function getDateTimeFormatterList()
    {
        return array_keys($this->getDateTimeFormatConverterRegistry()->getFormatConverters());
    }

    /**
     * @param string      $type
     * @param string|null $locale
     *
     * @return string
     */
    public function getDayFormat($type, $locale = null)
    {
        return $this->getDateTimeFormatConverterRegistry()
            ->getFormatConverter($type)
            ->getDayFormat($locale);
    }

    /**
     * @param string      $type
     * @param string|null $dateType
     * @param string|null $locale
     *
     * @return string
     */
    public function getDateFormat($type, $dateType = null, $locale = null)
    {
        return $this->getDateTimeFormatConverterRegistry()
            ->getFormatConverter($type)
            ->getDateFormat($dateType, $locale);
    }

    /**
     * @param string      $type
     * @param string|null $timeType
     * @param string|null $locale
     *
     * @return string
     */
    public function getTimeFormat($type, $timeType = null, $locale = null)
    {
        return $this->getDateTimeFormatConverterRegistry()
            ->getFormatConverter($type)
            ->getTimeFormat($timeType, $locale);
    }

    /**
     * @param string      $type
     * @param string|null $dateType
     * @param string|null $timeType
     * @param string|null $locale
     *
     * @return string
     */
    public function getDateTimeFormat($type, $dateType = null, $timeType = null, $locale = null)
    {
        return $this->getDateTimeFormatConverterRegistry()
            ->getFormatConverter($type)
            ->getDateTimeFormat($dateType, $timeType, $locale);
    }
}
