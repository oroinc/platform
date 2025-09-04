<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\EventListener;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class LocalizationChangeListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures(['@OroLocaleBundle/Tests/Functional/DataFixtures/localizations_data.yml']);
    }

    public function testChangeGlobalLocalizations(): void
    {
        $localizationId1 = $this->getReference('localization1')->getId();
        $localizationId2 = $this->getReference('german_localization')->getId();
        $userId = $this->getReference('user')->getId();

        $globalConfigManager = self::getConfigManager();
        $initialGlobalEnabledLocalizations = $globalConfigManager->get('oro_locale.enabled_localizations');
        $initialGlobalDefaultLocalization = $globalConfigManager->get('oro_locale.default_localization');

        $userConfigManager = self::getConfigManager('user');
        $userConfigManager->setScopeId($userId);
        $initialUserDefaultLocalization = $userConfigManager->get('oro_locale.default_localization');

        $globalConfigManager->set('oro_locale.enabled_localizations', [$localizationId1, $localizationId2]);
        $globalConfigManager->set('oro_locale.default_localization', $localizationId1);
        $globalConfigManager->flush();
        try {
            $userConfigManager->set('oro_locale.default_localization', $localizationId2);
            $userConfigManager->flush();
            $this->assertEquals($localizationId2, $userConfigManager->get('oro_locale.default_localization'));

            $globalConfigManager->set('oro_locale.enabled_localizations', [$localizationId1]);
            $globalConfigManager->flush();
            $this->assertEquals($localizationId1, $userConfigManager->get('oro_locale.default_localization'));
        } finally {
            $globalConfigManager->set('oro_locale.enabled_localizations', $initialGlobalEnabledLocalizations);
            $globalConfigManager->set('oro_locale.default_localization', $initialGlobalDefaultLocalization);
            $globalConfigManager->flush();

            $userConfigManager->set('oro_locale.default_localization', $initialUserDefaultLocalization);
            $userConfigManager->flush();
            $userConfigManager->reload();
        }
    }
}
