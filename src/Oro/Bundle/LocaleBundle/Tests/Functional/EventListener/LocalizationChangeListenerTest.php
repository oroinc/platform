<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class LocalizationChangeListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                '@OroLocaleBundle/Tests/Functional/DataFixtures/localizations_data.yml'
            ]
        );
    }

    public function testChangeGlobalLocalizations(): void
    {
        $localizationId1 = $this->getReference('localization1')->getId();
        $localizationId2 = $this->getReference('german_localization')->getId();
        $userId = $this->getReference('user')->getId();

        /** @var ConfigManager $globalConfigManager */
        $globalConfigManager = $this->getContainer()->get('oro_config.global');

        $globalConfigManager->set('oro_locale.enabled_localizations', [$localizationId1, $localizationId2]);
        $globalConfigManager->set('oro_locale.default_localization', $localizationId1);
        $globalConfigManager->flush();

        /** @var ConfigManager $userConfigManager */
        $userConfigManager = $this->getContainer()->get('oro_config.user');
        $userConfigManager->setScopeId($userId);
        $userConfigManager->set('oro_locale.default_localization', $localizationId2);
        $userConfigManager->flush();

        $this->assertEquals($localizationId2, $userConfigManager->get('oro_locale.default_localization'));

        $globalConfigManager->set('oro_locale.enabled_localizations', [$localizationId1]);
        $globalConfigManager->flush();

        $this->assertEquals($localizationId1, $userConfigManager->get('oro_locale.default_localization'));
    }
}
