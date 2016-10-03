<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Manager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * @dbIsolation
 */
class TranslationManagerTest extends WebTestCase
{
    /** @var TranslationManager */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadTranslations::class]);

        $this->manager = $this->getContainer()->get('oro_translation.manager.translation');
    }

    public function testCreateValue()
    {
        $key = uniqid('TEST_KEY_', true);
        $value = uniqid('TEST_VALUE', true);
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $translation = $this->manager->findValue($key, $locale, $domain);
        $this->assertNull($translation);

        $this->manager->saveValue($key, $value, $locale, $domain, Translation::SCOPE_UI);

        $this->manager->flush();

        $translation = $this->manager->findValue($key, $locale, $domain);

        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);
    }

    public function testUpdateScopeSystemValue()
    {
        $key = LoadTranslations::TRANSLATION_KEY_1;
        $value = uniqid('TEST_VALUE', true);
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $this->createValue($key, 'initial value', $locale, $domain, Translation::SCOPE_SYSTEM);

        $this->assertNotNull($this->manager->saveValue($key, $value, $locale, $domain, Translation::SCOPE_SYSTEM));
        $this->manager->flush();

        $translation = $this->manager->findValue($key, $locale, $domain);

        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);
    }

    public function testUpdateScopeInstalledValue()
    {
        $key = LoadTranslations::TRANSLATION_KEY_2;
        $value = uniqid('TEST_VALUE', true);
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $this->createValue($key, 'initial value', $locale, $domain, Translation::SCOPE_SYSTEM);

        $this->assertNotNull($this->manager->saveValue($key, $value, $locale, $domain, Translation::SCOPE_INSTALLED));
        $this->manager->flush();

        // Ensure That We Overwrite SCOPE_SYSTEM
        $translation = $this->manager->findValue($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);

        // Ensure That We cannot Overwrite SCOPE_INSTALLED
        $this->assertNull(
            $this->manager->saveValue($key, uniqid('', true), $locale, $domain, Translation::SCOPE_SYSTEM)
        );

        $translation = $this->manager->findValue($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);
    }

    public function testUpdateScopeUIValue()
    {
        $key = LoadTranslations::TRANSLATION_KEY_3;
        $value = uniqid('TEST_VALUE', true);
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $this->createValue($key, 'initial value', $locale, $domain, Translation::SCOPE_INSTALLED);

        $this->assertNotNull($this->manager->saveValue($key, $value, $locale, $domain, Translation::SCOPE_UI));
        $this->manager->flush();

        // Ensure That We Overwrite SCOPE_INSTALLED
        $translation = $this->manager->findValue($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);

        //Ensure That We cannot Overwrite SCOPE_UI
        $this->assertNull(
            $this->manager->saveValue($key, uniqid('', true), $locale, $domain, Translation::SCOPE_INSTALLED)
        );
        $translation = $this->manager->findValue($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);
    }

    public function testDeleteValue()
    {
        $key = LoadTranslations::TRANSLATION_KEY_3;
        $value = null;
        $locale = LoadLanguages::LANGUAGE2;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $this->createValue($key, 'initial value', $locale, $domain, Translation::SCOPE_UI);

        $this->assertNull($this->manager->saveValue($key, $value, $locale, $domain, Translation::SCOPE_UI));
        $this->manager->flush();

        $translation = $this->manager->findValue($key, $locale, $domain);
        $this->assertNull($translation);
    }

    public function testRebuildCache()
    {
        // build initial cache
        $this->manager->rebuildCache();

        $key = uniqid('TRANSLATION_KEY_', true);
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;
        $locale = LoadLanguages::LANGUAGE2;
        $expectedValue = uniqid('TEST_VALUE_', true);

        $this->manager->saveValue($key, $expectedValue, $locale, $domain, Translation::SCOPE_UI);

        $this->manager->flush();
        $this->manager->clear();

        /** @var Translator $translator */
        $translator = $this->getContainer()->get('translator');

        // Ensure that catalog still contains old translated value
        $this->assertNotEquals($expectedValue, $translator->trans($key, [], $domain, $locale));

        $this->manager->rebuildCache();

        // Ensure that catalog now contains new translated value
        $this->assertEquals($expectedValue, $translator->trans($key, [], $domain, $locale));
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $locale
     * @param string $domain
     * @param int $scope
     */
    protected function createValue($key, $value, $locale, $domain, $scope)
    {
        $this->manager->saveValue($key, $value, $locale, $domain, $scope);
        $this->manager->flush();
    }

    /**
     * @param Translation $translation
     * @param string $key
     * @param string $value
     * @param string $domain
     * @param string $locale
     */
    protected function ensureTranslationIsCorrect(Translation $translation, $key, $value, $domain, $locale)
    {
        $this->assertEquals($value, $translation->getValue());
        $this->assertInstanceOf(Language::class, $translation->getLanguage());
        $this->assertEquals($locale, $translation->getLanguage()->getCode());
        $this->assertInstanceOf(TranslationKey::class, $translation->getTranslationKey());
        $this->assertEquals($key, $translation->getTranslationKey()->getKey());
        $this->assertEquals($domain, $translation->getTranslationKey()->getDomain());
    }
}
