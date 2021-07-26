<?php

namespace Oro\Bundle\LocaleBundle\Tests\Behat\Context;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;

class LocalizationContext extends OroFeatureContext
{
    private ManagerRegistry $managerRegistry;

    private Translator $translator;

    private ConfigManager $globalScopeManager;

    private DoctrineHelper $doctrineHelper;

    private TranslationDomainProvider $translationDomainProvider;

    private JsTranslationDumper $jsTranslationDumper;

    private DynamicAssetVersionManager $dynamicAssetVersionManager;

    public function __construct(
        ManagerRegistry $managerRegistry,
        Translator $translator,
        ConfigManager $globalScopeManager,
        DoctrineHelper $doctrineHelper,
        TranslationDomainProvider $translationDomainProvider,
        JsTranslationDumper $jsTranslationDumper,
        DynamicAssetVersionManager $dynamicAssetVersionManager
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->translator = $translator;
        $this->globalScopeManager = $globalScopeManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->translationDomainProvider = $translationDomainProvider;
        $this->jsTranslationDumper = $jsTranslationDumper;
        $this->dynamicAssetVersionManager = $dynamicAssetVersionManager;
    }

    /**
     * @Given I enable the existing localizations
     */
    public function loadFixtures()
    {
        /* @var Localization[] $localizations */
        $localizations = $this->doctrineHelper
            ->getEntityRepository(Localization::class)
            ->findAll();

        $this->globalScopeManager->set(
            'oro_locale.enabled_localizations',
            array_map(function (Localization $item) {
                return $item->getId();
            }, $localizations)
        );
        $this->globalScopeManager->flush();

        $this->translationDomainProvider->clearCache();
        $this->translator->rebuildCache();
        $this->jsTranslationDumper->dumpTranslations();
        $this->dynamicAssetVersionManager->updateAssetVersion('translations');
    }

    /**
     * @Given /^I enable "(?P<localizationName>(?:[^"]|\\")*)" localization/
     *
     * @param string $localizationName
     */
    public function selectLocalization($localizationName)
    {
        $localization = $this->managerRegistry->getManagerForClass(Localization::class)
            ->getRepository(Localization::class)
            ->findOneBy(['name' => $localizationName]);

        $this->globalScopeManager->set(
            'oro_locale.enabled_localizations',
            array_unique(
                array_merge(
                    $this->globalScopeManager->get('oro_locale.enabled_localizations'),
                    [$localization->getId()]
                )
            )
        );
        $this->globalScopeManager->set('oro_locale.default_localization', $localization->getId());
        $this->globalScopeManager->flush();
    }
}
