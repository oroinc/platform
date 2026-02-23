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
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
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

    public function getDateTimeFormatterList(): array
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

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            DateTimeFormatConverterRegistry::class
        ];
    }

    private function getDateTimeFormatConverterRegistry(): DateTimeFormatConverterRegistry
    {
        return $this->container->get(DateTimeFormatConverterRegistry::class);
    }
}
