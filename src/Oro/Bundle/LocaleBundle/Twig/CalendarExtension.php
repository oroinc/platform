<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to retrieve calendar data:
 *   - oro_calendar_month_names
 *   - oro_calendar_day_of_week_names
 *   - oro_calendar_first_day_of_week
 */
class CalendarExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?LocaleSettings $localeSettings = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_calendar_month_names',
                [$this, 'getMonthNames']
            ),
            new TwigFunction(
                'oro_calendar_day_of_week_names',
                [$this, 'getDayOfWeekNames']
            ),
            new TwigFunction(
                'oro_calendar_first_day_of_week',
                [$this, 'getFirstDayOfWeek']
            ),
        ];
    }

    /**
     * Gets list of months names using given width and locale.
     *
     * @param string      $width wide|abbreviation|short|narrow
     * @param string|null $locale
     *
     * @return string[]
     */
    public function getMonthNames($width = null, $locale = null)
    {
        return $this->getLocaleSettings()->getCalendar($locale)->getMonthNames($width);
    }

    /**
     * Gets list of week day names using given width and locale.
     *
     * @param string      $width wide|abbreviation|short|narrow
     * @param string|null $locale
     *
     * @return string[]
     */
    public function getDayOfWeekNames($width = null, $locale = null)
    {
        return $this->getLocaleSettings()->getCalendar($locale)->getDayOfWeekNames($width);
    }

    /**
     * Gets first day of week according to constants of Calendar.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getFirstDayOfWeek($locale = null)
    {
        return $this->getLocaleSettings()->getCalendar($locale)->getFirstDayOfWeek();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            LocaleSettings::class,
        ];
    }

    private function getLocaleSettings(): LocaleSettings
    {
        if (null === $this->localeSettings) {
            $this->localeSettings = $this->container->get(LocaleSettings::class);
        }

        return $this->localeSettings;
    }
}
