<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\PhpUtils\ArrayUtil;

class LocalizationChangeListenerTest extends WebTestCase
{
    private const CONFIG_GLOBAL = 'oro_config.global';
    private const CONFIG_USER = 'oro_config.user';
    private const CONFIG_ORGANIZATION = 'oro_config.organization';

    private const GERMAN_LOCALIZATION = 'German Localization';
    private const CANADA_LOCALIZATION = 'English (Canada)';

    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var ConfigManager */
    private $configManager;

    /** @var Organization */
    private $organization;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                '@OroLocaleBundle/Tests/Functional/DataFixtures/localizations_data.yml'
            ]
        );

        $this->managerRegistry = $this->getContainer()->get('doctrine');
        $this->configManager = $this->getContainer()->get('oro_config.manager');
        $this->organization = $this->getReference('organization');
    }
    
    public function testResetToDefaultUserLocalization()
    {
        $canadaLocalizationId = $this->getLocalizationId(self::CANADA_LOCALIZATION);
        $germanLocalizationId = $this->getLocalizationId(self::GERMAN_LOCALIZATION);

        $this->setEnabledLocalizations([$canadaLocalizationId, $germanLocalizationId]);

        $userLocalization = $this->setUserLocalization($germanLocalizationId);
        $this->assertEquals($germanLocalizationId, $userLocalization);

        $globalLocalization = $this->setGlobalLocalization($canadaLocalizationId);
        $this->assertEquals($canadaLocalizationId, $globalLocalization);

        $userConfigManager = $this->getContainer()->get(self::CONFIG_USER);
        $this->assertEquals($globalLocalization, $userConfigManager->get('oro_locale.default_localization'));
    }

    public function testResetToDefaultOrganizationLocalization()
    {
        $canadaLocalizationId = $this->getLocalizationId(self::CANADA_LOCALIZATION);
        $germanLocalizationId = $this->getLocalizationId(self::GERMAN_LOCALIZATION);

        $organizationLocalization = $this->setOrganizationLocalization($canadaLocalizationId);
        $this->assertEquals($canadaLocalizationId, $organizationLocalization);

        $globalLocalization = $this->setGlobalLocalization($germanLocalizationId);
        $this->assertEquals($germanLocalizationId, $globalLocalization);

        $organizationConfigManager = $this->getContainer()->get(self::CONFIG_ORGANIZATION);
        $this->assertEquals($globalLocalization, $organizationConfigManager->get('oro_locale.default_localization'));
    }

    /**
     * Sets the list of available localizations
     *
     * @param array $localizations
     */
    private function setEnabledLocalizations(array $localizations): void
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_config_configuration_system',
                ['activeGroup' => 'platform', 'activeSubGroup' => 'localization']
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $token = static::getContainer()
            ->get('security.csrf.token_manager')
            ->getToken('localization')
            ->getValue();

        $form = $crawler->selectButton('Save settings')->form();
        $formData = ArrayUtil::arrayMergeRecursiveDistinct(
            $form->getPhpValues(),
            [
                'localization' => [
                    'oro_locale___enabled_localizations' => [
                        'use_parent_scope_value' => false,
                        'value' => $localizations,
                    ],
                    '_token' => $token,
                ],
            ]
        );

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formData);
    }

    /**
     * Set global localization value
     *
     * @param int $localizationId
     * @return string
     */
    private function setGlobalLocalization(int $localizationId): string
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_config_configuration_system',
                ['activeGroup' => 'platform', 'activeSubGroup' => 'localization']
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $token = static::getContainer()
            ->get('security.csrf.token_manager')
            ->getToken('localization')
            ->getValue();

        $form = $crawler->selectButton('Save settings')->form();
        $formData = ArrayUtil::arrayMergeRecursiveDistinct(
            $form->getPhpValues(),
            [
                'localization' => [
                    'oro_locale___default_localization' => [
                        'use_parent_scope_value' => false,
                        'value' => $localizationId,
                    ],
                    '_token' => $token,
                ],
            ]
        );

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        return $this->getContainer()->get(self::CONFIG_GLOBAL)->get('oro_locale.default_localization');
    }

    /**
     * Set user localization value
     *
     * @param int $localizationId
     * @return string
     */
    private function setUserLocalization(int $localizationId): string
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_user_profile_configuration',
                ['activeGroup' => 'platform', 'activeSubGroup' => 'localization']
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $token = static::getContainer()
            ->get('security.csrf.token_manager')
            ->getToken('localization')
            ->getValue();

        $form = $crawler->selectButton('Save settings')->form();
        $formData = ArrayUtil::arrayMergeRecursiveDistinct(
            $form->getPhpValues(),
            [
                'localization' => [
                    'oro_locale___default_localization' => [
                        'use_parent_scope_value' => false,
                        'value' => $localizationId,
                    ],
                    '_token' => $token,
                ],
            ]
        );

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        return $this->getContainer()->get(self::CONFIG_USER)->get('oro_locale.default_localization');
    }

    /**
     * Set organization localization value
     *
     * @param int $localizationId
     * @return string
     */
    private function setOrganizationLocalization(int $localizationId): string
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_organization_config',
                ['id' => $this->organization->getId(), 'activeGroup' => 'platform', 'activeSubGroup' => 'localization']
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $token = static::getContainer()
            ->get('security.csrf.token_manager')
            ->getToken('localization')
            ->getValue();

        $form = $crawler->selectButton('Save settings')->form();
        $formData = ArrayUtil::arrayMergeRecursiveDistinct(
            $form->getPhpValues(),
            [
                'localization' => [
                    'oro_locale___default_localization' => [
                        'use_parent_scope_value' => false,
                        'value' => $localizationId,
                    ],
                    '_token' => $token,
                ],
            ]
        );

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        return $this->getContainer()->get(self::CONFIG_ORGANIZATION)->get('oro_locale.default_localization');
    }

    /**
     * Return id of needed localization
     *
     * @param string $localizationName
     * @return int
     */
    private function getLocalizationId(string $localizationName): int
    {
        $localization = $this->managerRegistry
            ->getRepository(Localization::class)
            ->findOneBy(['name' => $localizationName]);

        return $localization->getId();
    }
}
