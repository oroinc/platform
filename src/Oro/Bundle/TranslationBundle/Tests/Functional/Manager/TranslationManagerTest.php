<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
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

        return ['key' => $key, 'locale' => $locale];
    }

    public function testUpdateScopeSystemValue()
    {
        $key = LoadTranslations::TRANSLATION_KEY_1;
        $value = uniqid('TEST_VALUE', true);
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $translation = $this->manager->findValue($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, LoadTranslations::TRANSLATION1, $domain, $locale);

        $this->manager->saveValue($key, $value, $locale, $domain, Translation::SCOPE_SYSTEM);

        $this->manager->flush();

        $translation = $this->manager->findValue($key, $locale, $domain);

        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);
    }

    public function testUpdateScopeUIValue()
    {
        $key = LoadTranslations::TRANSLATION_KEY_2;
        $value = uniqid('TEST_VALUE', true);
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $translation = $this->manager->findValue($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, LoadTranslations::TRANSLATION2, $domain, $locale);

        $this->assertNotNull($this->manager->saveValue($key, $value, $locale, $domain, Translation::SCOPE_UI));

        $this->manager->flush();

        $translation = $this->manager->findValue($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);

        //Ensure That We cannot Overwrite SCOPE_UI
        $this->assertNull(
            $this->manager->saveValue($key, uniqid('', true), $locale, $domain, Translation::SCOPE_SYSTEM)
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

        $translation = $this->manager->findValue($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, LoadTranslations::TRANSLATION3, $domain, $locale);

        $this->manager->saveValue($key, $value, $locale, $domain, Translation::SCOPE_UI);

        $this->manager->flush();

        $translation = $this->manager->findValue($key, $locale, $domain);
        $this->assertNull($translation);
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

//    public function testRebuildCache()
//    {
//        $key = uniqid('TRANSLATION_KEY_', true);
//        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;
//        $locale = LoadLanguages::LANGUAGE2;
//        $expectedValue = uniqid('TEST_VALUE_', true);
//
//        /** @var Translator $translator */
//        $translator = $this->getContainer()->get('translator.default');
//
//        $this->manager->saveValue($key, $expectedValue, $locale, $domain);
//        $this->manager->flush();
//        $this->manager->clear();
//
//        //Ensure that catalog still contains old translated value
//        $actualValue = $translator->trans($key, [], $domain, $locale);
//        $this->assertNotEquals($expectedValue, $actualValue);
//
//        /** @var Translator $translator */
//        $translator = $this->getContainer()->get('translator.default');
//
//        $this->manager->rebuildCache();
//
//        //Ensure that catalog now contains new translated value
//        $actualValue = $translator->trans($key, [], $domain, $locale);
//        $this->assertEquals($expectedValue, $actualValue);
//    }
}
