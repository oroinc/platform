<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Helper\LanguageHelper;
use Oro\Bundle\TranslationBundle\Provider\ExternalTranslationsProvider;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;

/**
 * @dbIsolationPerTest
 */
class LanguageOperationsTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadLanguages::class,]);
        $this->client->disableReboot();
    }

    public function testEnableLanguage()
    {
        /** @var Language $language */
        $language = $this->getReference(LoadLanguages::LANGUAGE1);

        $this->assertFalse($language->isEnabled());
        $this->assertExecuteOperation('oro_translation_language_enable', $language->getId(), Language::class);
        $language = $this->getReference(LoadLanguages::LANGUAGE1);
        $this->assertTrue($language->isEnabled());
    }

    public function testDisableLanguage()
    {
        /** @var Language $language1 */
        $language = $this->getReference(LoadLanguages::LANGUAGE1);
        $language->setEnabled(true);

        $this->assertTrue($language->isEnabled());
        $this->assertExecuteOperation('oro_translation_language_disable', $language->getId(), Language::class);
        $language = $this->getReference(LoadLanguages::LANGUAGE1);
        $this->assertFalse($language->isEnabled());
    }

    public function testAddLanguage()
    {
        /** @var Language $language */
        $language = $this->getReference(LoadLanguages::LANGUAGE1);
        $language->setEnabled(true);

        $this->assertTrue($language->isEnabled());
        $crawler = $this->assertOperationForm('oro_translation_language_add', $language->getId(), Language::class);
        $form = $crawler->selectButton('Add Language')->form([
            'oro_action_operation[language_code]' => 'zu_ZA',
        ]);
        $this->assertOperationFormSubmitted($form, 'Language has been added');
    }

    public function testInstallLanguage()
    {
        /** @var Language $language */
        $language = $this->getReference(LoadLanguages::LANGUAGE1);
        $this->assertLanguageHelperCalled('isAvailableInstallTranslates');
        $this->assertExternalTranslationsProviderCalled();

        $crawler = $this->assertOperationForm(
            'oro_translation_language_install',
            $language->getId(),
            Language::class
        );

        $form = $crawler->selectButton('Install')->form([
            'oro_action_operation[language_code]' => LoadLanguages::LANGUAGE1,
        ]);

        $this->assertOperationFormSubmitted($form, 'Language has been installed');
    }

    public function testUpdateLanguage()
    {
        /** @var Language $language */
        $language = $this->getReference(LoadLanguages::LANGUAGE2);
        $this->assertLanguageHelperCalled('isAvailableUpdateTranslates');
        $this->assertExternalTranslationsProviderCalled();

        $crawler = $this->assertOperationForm(
            'oro_translation_language_update',
            $language->getId(),
            Language::class
        );

        $form = $crawler->selectButton('Update')->form([
            'oro_action_operation[language_code]' => LoadLanguages::LANGUAGE2,
        ]);

        $this->assertOperationFormSubmitted($form, 'Language has been updated');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ExternalTranslationsProvider
     */
    private function assertExternalTranslationsProviderCalled()
    {
        $provider = $this->createMock(ExternalTranslationsProvider::class);
        $provider->expects($this->atLeastOnce())->method('updateTranslations')->willReturn(true);

        $this->getContainer()->set('oro_translation.provider.external_translations', $provider);

        return $provider;
    }

    /**
     * @param string $method
     *
     * @return LanguageHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertLanguageHelperCalled($method)
    {
        $languageHelper = $this->createMock(LanguageHelper::class);
        $languageHelper->expects($this->atLeastOnce())->method($method)->willReturn(true);

        $this->getContainer()->set('oro_translation.helper.language', $languageHelper);

        return $languageHelper;
    }
}
