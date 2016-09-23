<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Manager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

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
        $key = uniqid('TEST_KEY_');
        $value = uniqid('TEST_VALUE');
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $translation = $this->manager->findValue($key, $locale, $domain);
        $this->assertNull($translation);

        $this->manager->createValue($key, $value, $locale, $domain, true);

        $this->manager->flush();

        $translation = $this->manager->findValue($key, $locale, $domain);

        $this->ensureTanslationIsCorrect($translation, $key, $value, $domain, $locale);
    }

    /**
     * @param Translation $translation
     * @param string $key
     * @param string $value
     * @param string $domain
     * @param string $locale
     */
    protected function ensureTanslationIsCorrect(Translation $translation, $key, $value, $domain, $locale)
    {
        $this->assertEquals($value, $translation->getValue());
        $this->assertInstanceOf(Language::class, $translation->getLanguage());
        $this->assertEquals($locale, $translation->getLanguage()->getCode());
        $this->assertInstanceOf(TranslationKey::class, $translation->getTranslationKey());
        $this->assertEquals($key, $translation->getTranslationKey()->getKey());
        $this->assertEquals($domain, $translation->getTranslationKey()->getDomain());
    }
}
