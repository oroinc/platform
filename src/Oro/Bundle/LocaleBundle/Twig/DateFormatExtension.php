<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to format dates:
 *   - oro_datetime_formatter_list
 *   - oro_day_format
 *   - oro_date_format
 *   - oro_time_format
 *   - oro_datetime_format
 */
class DateFormatExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

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
            new TwigFunction('oro_datetime_formatter_list', [$this, 'getDateTimeFormatterList']),
            new TwigFunction('oro_day_format', [$this, 'getDayFormat']),
            new TwigFunction('oro_date_format', [$this, 'getDateFormat']),
            new TwigFunction('oro_time_format', [$this, 'getTimeFormat']),
            new TwigFunction('oro_datetime_format', [$this, 'getDateTimeFormat']),
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_locale.format_converter.date_time.registry' => DateTimeFormatConverterRegistry::class,
        ];
    }
}
