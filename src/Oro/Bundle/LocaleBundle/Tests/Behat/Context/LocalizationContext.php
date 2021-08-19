<?php

namespace Oro\Bundle\LocaleBundle\Tests\Behat\Context;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LocalizationContext extends OroFeatureContext
{
    /**
     * @Given I enable the existing localizations
     */
    public function loadFixtures()
    {
        $container = $this->getAppContainer();

        /* @var ConfigManager $configManager */
        $configManager = $container->get('oro_config.global');

        /* @var Localization[] $localizations */
        $localizations = $container
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository(Localization::class)
            ->findAll();

        $configManager->set(
            'oro_locale.enabled_localizations',
            array_map(function (Localization $item) {
                return $item->getId();
            }, $localizations)
        );
        $configManager->flush();

        $container->get('oro_translation.provider.translation_domain')->clearCache();
        $container->get('translator.default')->rebuildCache();
        $container->get('oro_translation.js_dumper')->dumpTranslations();
        $container->get('oro_ui.dynamic_asset_version_manager')->updateAssetVersion('translations');
    }

    /**
     * @Given /^I enable "(?P<localizationName>(?:[^"]|\\")*)" localization/
     *
     * @param string $localizationName
     */
    public function selectLocalization($localizationName)
    {
        /** @var ContainerInterface $container */
        $container = $this->getAppContainer();

        $localization = $container->get('doctrine')->getManagerForClass(Localization::class)
            ->getRepository(Localization::class)
            ->findOneBy(['name' => $localizationName]);

        /** @var ConfigManager $configManager */
        $configManager = $container->get('oro_config.global');
        $configManager->set(
            'oro_locale.enabled_localizations',
            array_unique(
                array_merge(
                    $configManager->get('oro_locale.enabled_localizations'),
                    [$localization->getId()]
                )
            )
        );
        $configManager->set('oro_locale.default_localization', $localization->getId());
        $configManager->flush();
    }
}
