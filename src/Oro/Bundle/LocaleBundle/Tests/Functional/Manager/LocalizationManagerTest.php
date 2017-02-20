<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Manager;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class LocalizationManagerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadLocalizationData::class]);
    }

    public function testLocalizationsCache()
    {
        $localizationManager = $this->setUpLocalizationManager();
        $localizations = $localizationManager->getLocalizations();

        /** @var Localization $enCALocalization */
        $enCALocalization = $this->getReference('en_CA');
        $enCALocalizationFromCache = $localizations[$enCALocalization->getId()];

        $this->assertEquals(
            $enCALocalization->getParentLocalization()->getId(),
            $enCALocalizationFromCache->getParentLocalization()->getId()
        );
        $this->assertEquals(
            $enCALocalization->getParentLocalization()->getTitles()->count(),
            $enCALocalizationFromCache->getParentLocalization()->getTitles()->count()
        );
    }

    public function testLocalizationCache()
    {
        $localizationManager = $this->setUpLocalizationManager();

        /** @var Localization $enCALocalization */
        $enCALocalization = $this->getReference('en_CA');

        $localizationFromCache = $localizationManager->getLocalization($enCALocalization->getId());

        $this->assertEquals(
            $enCALocalization->getParentLocalization()->getId(),
            $localizationFromCache->getParentLocalization()->getId()
        );
        $this->assertEquals(
            $enCALocalization->getParentLocalization()->getTitles()->count(),
            $localizationFromCache->getParentLocalization()->getTitles()->count()
        );
    }

    /**
     * @return object|\Oro\Bundle\LocaleBundle\Manager\LocalizationManager
     */
    private function setUpLocalizationManager()
    {
        //Clear cache
        $this->getContainer()->get('oro_locale.manager.localization')->clearCache();
        // Store localizations in cache
        $this->getContainer()->get('oro_locale.manager.localization')->warmUpCache();

        return $this->getContainer()->get('oro_locale.manager.localization');
    }
}
