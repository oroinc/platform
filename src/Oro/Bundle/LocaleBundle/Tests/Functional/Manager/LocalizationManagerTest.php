<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Manager;

use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class LocalizationManagerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
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

    public function testGetLocalizationsData()
    {
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $em = $doctrineHelper->getEntityManagerForClass(Localization::class);
        $conn = $em->getConnection();
        $sqlLogger = new QueryAnalyzer($conn->getDatabasePlatform());
        $conn->getConfiguration()->setSQLLogger($sqlLogger);
        $cache = new ArrayAdapter(0, true);

        $manager = new LocalizationManager(
            $doctrineHelper,
            self::getConfigManager(),
            $cache
        );

        $this->assertSame([], $manager->getLocalizationData(0));
        $this->assertSame([], $manager->getLocalizationData(0, false));

        $this->assertSame([
            'languageCode' => 'en',
            'formattingCode' => 'en_US',
            'rtlMode' => false,
        ], $manager->getLocalizationData($this->getReference('en_US')->getId()));

        $this->assertSame([
            'languageCode' => 'en_CA',
            'formattingCode' => 'en_CA',
            'rtlMode' => false,
        ], $manager->getLocalizationData($this->getReference('en_CA')->getId()));

        $this->assertSame([
            'languageCode' => 'es',
            'formattingCode' => 'es',
            'rtlMode' => false,
        ], $manager->getLocalizationData($this->getReference('es')->getId(), false));

        $this->assertCount(3, $sqlLogger->getExecutedQueries());
    }

    private function setUpLocalizationManager(): LocalizationManager
    {
        //Clear cache
        $this->getContainer()->get('oro_locale.manager.localization')->clearCache();
        // Store localizations in cache
        $this->getContainer()->get('oro_locale.manager.localization')->warmUpCache();

        return $this->getContainer()->get('oro_locale.manager.localization');
    }
}
