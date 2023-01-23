<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslationUsers;
use Oro\Bundle\UserBundle\Entity\User;

class LanguageRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTranslations::class]);

        /* @var User $user */
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)
            ->findOneBy(['username' => LoadTranslationUsers::TRANSLATOR_USERNAME]);

        $token = new UsernamePasswordOrganizationToken(
            $user,
            false,
            'k',
            $user->getOrganization(),
            $user->getUserRoles()
        );
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
    }

    private function getRepository(): LanguageRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Language::class);
    }

    public function testGetAvailableLanguageCodesAsArrayKeys(): void
    {
        $defaultAndLoadedLanguageCodes = array_merge(
            [Configuration::DEFAULT_LANGUAGE],
            array_keys(LoadLanguages::LANGUAGES)
        );

        self::assertEqualsCanonicalizing(
            array_fill_keys($defaultAndLoadedLanguageCodes, true),
            $this->getRepository()->getAvailableLanguageCodesAsArrayKeys()
        );
    }

    public function testGetAvailableLanguagesByCurrentUser()
    {
        /* @var AclHelper $aclHelper */
        $aclHelper = self::getContainer()->get('oro_security.acl_helper');

        self::assertEquals(
            [
                'en',
                $this->getReference(LoadLanguages::LANGUAGE1)->getCode(),
                $this->getReference(LoadLanguages::LANGUAGE2)->getCode(),
                $this->getReference(LoadLanguages::LANGUAGE3)->getCode(),
            ],
            array_map(
                fn (Language $language) => $language->getCode(),
                $this->getRepository()->getAvailableLanguagesByCurrentUser($aclHelper)
            )
        );
    }

    public function testGetTranslationsForExport()
    {
        $expected = [
            [
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                'key' => LoadTranslations::TRANSLATION_KEY_1,
                'value' => LoadTranslations::TRANSLATION_KEY_1,
                'english_translation' => LoadTranslations::TRANSLATION_KEY_1,
                'is_translated' => 1,
            ],
            [
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                'key' => LoadTranslations::TRANSLATION_KEY_2,
                'value' => LoadTranslations::TRANSLATION_KEY_2,
                'english_translation' => LoadTranslations::TRANSLATION_KEY_2,
                'is_translated' => 1,
            ],
        ];

        $result = $this->getRepository()->getTranslationsForExport(LoadLanguages::LANGUAGE1);

        foreach ($expected as $translation) {
            self::assertTrue(in_array($translation, $result));
        }
    }

    /**
     * @dataProvider getLanguagesDataProvider
     */
    public function testGetLanguages(array $expected, bool $enabled)
    {
        $current = array_map(function (Language $lang) {
            return $lang->getCode();
        }, $this->getRepository()->getLanguages($enabled));

        self::assertEquals($expected, $current);
    }

    public function getLanguagesDataProvider(): array
    {
        return [
            'only enabled' => [
                'expected' => [
                    'en',
                    LoadLanguages::LANGUAGE2,
                    LoadLanguages::LANGUAGE3,
                ],
                'enabled' => true,
            ],
            'all' => [
                'expected' => [
                    'en',
                    LoadLanguages::LANGUAGE1,
                    LoadLanguages::LANGUAGE2,
                    LoadLanguages::LANGUAGE3,
                ],
                'enabled' => false,
            ]
        ];
    }
}
