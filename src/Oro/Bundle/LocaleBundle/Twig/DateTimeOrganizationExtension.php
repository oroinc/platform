<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Twig\TwigFilter;

/**
 * Overrides existing Twig filters to use the default localization settings of the current organization:
 *   - oro_format_datetime
 *   - oro_format_date
 *   - oro_format_day
 *   - oro_format_time
 *
 * It also provides a new Twig filter to explicitly use the organization localization settings:
 *   - oro_format_datetime_organization
 *
 * It's a temporary workaround to fix dates in notification emails until CRM-5758 is implemented.
 */
class DateTimeOrganizationExtension extends DateTimeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $filters = parent::getFilters();
        $filters[] = new TwigFilter(
            'oro_format_datetime_organization',
            [$this, 'formatDateTimeOrganization']
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
     * @param array                $options
     *
     * @return string
     */
    public function formatDateTimeOrganization($date, array $options = [])
    {
        [$locale, $timeZone] = $this->getLocaleSettings($options['organization'] ?? null, $options);

        return $this->getDateTimeFormatter()->format(
            $date,
            $options['dateType'] ?? null,
            $options['timeType'] ?? null,
            $locale,
            $timeZone
        );
    }

    /**
     * @param OrganizationInterface|null $organization
     * @param array                      $options
     *
     * @return array [locale, timezone]
     */
    protected function getLocaleSettings($organization, array $options)
    {
        if ($organization instanceof OrganizationInterface) {
            $configManager = $this->getConfigManager();
            $localizationId = $configManager->get(
                Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION)
            );

            $locale = $this->getFormattingCode((int) $localizationId);
            $timeZone = $configManager->get('oro_locale.timezone');
        } else {
            $locale = $options['locale'] ?? null;
            $timeZone = $options['timeZone'] ?? null;
        }

        return [$locale, $timeZone];
    }

    /**
     * @param int $localizationId
     * @return string
     */
    protected function getFormattingCode(int $localizationId)
    {
        $localizationData = $this->getLocalizationManager()->getLocalizationData($localizationId);

        return $localizationData['formattingCode'] ?? Configuration::DEFAULT_LOCALE;
    }

    /**
     * {@inheritdoc]
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                'oro_config.global' => ConfigManager::class,
                'oro_locale.manager.localization' => LocalizationManager::class,
            ]
        );
    }
    protected function getConfigManager(): ConfigManager
    {
        return $this->container->get('oro_config.global');
    }

    protected function getLocalizationManager(): LocalizationManager
    {
        return $this->container->get('oro_locale.manager.localization');
    }
}
