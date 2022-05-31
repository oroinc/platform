<?php

namespace Oro\Bundle\LocaleBundle\Tests\Behat\Context;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class LocalizationContext extends OroFeatureContext
{
    /**
     * @Given I enable the existing localizations
     */
    public function loadFixtures(): void
    {
        $container = $this->getAppContainer();

        /* @var Localization[] $localizations */
        $localizations = $container->get('doctrine')
            ->getRepository(Localization::class)
            ->findAll();
        $localizationsIds = [];
        $locales = [];
        foreach ($localizations as $localization) {
            $localizationsIds[] = $localization->getId();
            $locales[] = $localization->getLanguage()->getCode();
        }
        $locales = array_unique($locales);

        /* @var ConfigManager $configManager */
        $configManager = $container->get('oro_config.global');
        $configManager->set('oro_locale.enabled_localizations', $localizationsIds);
        $configManager->flush();

        $container->get('oro_translation.provider.translation_domain')->clearCache();
        $container->get('oro_translation.dynamic_translation_cache')->delete($locales);
        $container->get('oro_translation.js_dumper')->dumpTranslations();
        $container->get('oro_ui.dynamic_asset_version_manager')->updateAssetVersion('translations');
    }

    /**
     * @Given /^I enable "(?P<localizationName>(?:[^"]|\\")*)" localization/
     */
    public function selectLocalization(string $localizationName): void
    {
        $container = $this->getAppContainer();

        /* @var Localization $localization */
        $localization = $container->get('doctrine')
            ->getRepository(Localization::class)
            ->findOneBy(['name' => $localizationName]);

        /** @var ConfigManager $configManager */
        $configManager = $container->get('oro_config.global');
        $configManager->set(
            'oro_locale.enabled_localizations',
            array_unique(
                array_merge($configManager->get('oro_locale.enabled_localizations'), [$localization->getId()])
            )
        );
        $configManager->set('oro_locale.default_localization', $localization->getId());
        $configManager->flush();
    }
}
