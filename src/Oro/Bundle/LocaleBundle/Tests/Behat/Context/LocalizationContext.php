<?php

namespace Oro\Bundle\LocaleBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LocalizationContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * @Given I enable the existing localizations
     */
    public function loadFixtures()
    {
        /** @var ContainerInterface $container */
        $container = $this->getContainer();

        /* @var $configManager ConfigManager */
        $configManager = $container->get('oro_config.global');

        /* @var $localizations Localization[] */
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
     * @Given /^I enable "(?P<languageCode>(?:[^"]|\\")*)" language$/
     *
     * @param string $languageCode
     */
    public function selectLanguage($languageCode)
    {
        /** @var ContainerInterface $container */
        $container = $this->getContainer();

        /** @var ConfigManager $configManager */
        $configManager = $container->get('oro_config.global');
        $configManager->set('oro_locale.languages', array_unique(['en', $languageCode]));
        $configManager->set('oro_locale.language', $languageCode);
        $configManager->flush();
    }
}
