<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * DateTimeUserExtension allows get formatted date and calendar date range by user localization settings
 * @package Oro\Bundle\LocaleBundle\Twig
 *
 * @deprecated Since 1.11, will be removed after 1.13.
 *
 * @todo: it's a temporary workaround to fix dates in reminder emails CRM-5745 until improvement CRM-5758 is implemented
 */
class DateTimeUserExtension extends DateTimeExtension
{
    /**
     * @var DateTimeFormatter
     */
    protected $formatter;

    /**
     * @var ConfigManager
     */
    protected $configManager;

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
    public function getFilters()
    {
        $filters = parent::getFilters();
        $filters[] = new \Twig_SimpleFilter(
            'oro_format_datetime_user',
            [$this, 'formatDateTimeUser'],
            ['is_safe' => ['html']]
        );
        return $filters;
    }

    /**
     * Formats date time according to user organization locale settings.
     * If user not passed used localization settings from params
     *
     * Options format:
     * array(
     *     'dateType' => <dateType>,
     *     'timeType' => <timeType>,
     *     'locale' => <locale>,
     *     'timezone' => <timezone>,
     *     'user' => <user>,
     * )
     *
     * @param \DateTime|string|int $date
     * @param array $options
     * @return string
     */
    public function formatDateTimeUser($date, array $options = [])
    {
        $dateType = $this->getOption($options, 'dateType');
        $timeType = $this->getOption($options, 'timeType');
        $user = $this->getOption($options, 'user');

        /** Get locale and datetime settings from local configuration if user set */
        if ($user) {
            $locale = $this->configManager->get('oro_locale.locale');
            $timeZone = $this->configManager->get('oro_locale.timezone');
        } else {
            $locale = $this->getOption($options, 'locale');
            $timeZone = $this->getOption($options, 'timeZone');
        }

        $result = $this->formatter->format($date, $dateType, $timeType, $locale, $timeZone);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_locale_datetime_user';
    }
}
