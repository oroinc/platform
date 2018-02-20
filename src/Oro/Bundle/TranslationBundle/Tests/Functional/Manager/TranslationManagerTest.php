<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Manager;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

class TranslationManagerTest extends WebTestCase
{
    /** @var TranslationManager */
    protected $manager;

    /** @var TranslationRepository */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadTranslations::class]);

        $this->manager = $this->getContainer()->get('oro_translation.manager.translation');
        $this->repository = $this->getRepository(Translation::class);
    }

    public function testCreateTranslation()
    {
        $key = uniqid('TEST_KEY_', true);
        $value = uniqid('TEST_VALUE', true);
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $translation = $this->repository->findTranslation($key, $locale, $domain);
        $this->assertNull($translation);

        $this->manager->saveTranslation($key, $value, $locale, $domain, Translation::SCOPE_UI);
        $this->manager->flush();

        $translation = $this->repository->findTranslation($key, $locale, $domain);

        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);
    }

    public function testCreateWithEmptyValue()
    {
        $key = uniqid('TEST_KEY_', true);
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $this->assertNotNull($this->manager->saveTranslation($key, '', $locale, $domain, Translation::SCOPE_UI));
        $this->manager->flush();

        $this->assertNotNull($this->repository->findTranslation($key, $locale, $domain));
    }

    public function testCreateWithNullValue()
    {
        $key = uniqid('TEST_KEY_', true);
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $this->assertNotNull($this->manager->saveTranslation(
            $key,
            uniqid('TEST_VALUE_', true),
            $locale,
            $domain,
            Translation::SCOPE_UI
        ));
        $this->manager->flush();

        $this->assertNull($this->manager->saveTranslation($key, null, $locale, $domain, Translation::SCOPE_UI));
        $this->manager->flush();

        $this->assertNull($this->repository->findTranslation($key, $locale, $domain));
    }

    public function testUpdateScopeSystemValue()
    {
        $key = LoadTranslations::TRANSLATION_KEY_1;
        $value = uniqid('TEST_VALUE', true);
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $this->createTranslation($key, 'initial value', $locale, $domain, Translation::SCOPE_SYSTEM);

        $this->assertNotNull(
            $this->manager->saveTranslation($key, $value, $locale, $domain, Translation::SCOPE_SYSTEM)
        );
        $this->manager->flush();

        $translation = $this->repository->findTranslation($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);
    }

    public function testUpdateScopeInstalledValue()
    {
        $key = LoadTranslations::TRANSLATION_KEY_2;
        $value = uniqid('TEST_VALUE', true);
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $this->createTranslation($key, 'initial value', $locale, $domain, Translation::SCOPE_SYSTEM);

        // Ensure That We Overwrite SCOPE_SYSTEM
        $this->assertNotNull(
            $this->manager->saveTranslation($key, $value, $locale, $domain, Translation::SCOPE_INSTALLED)
        );
        $this->manager->flush();

        $translation = $this->repository->findTranslation($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);

        // Ensure That We cannot Overwrite SCOPE_INSTALLED
        $this->assertNull(
            $this->manager->saveTranslation($key, uniqid('', true), $locale, $domain, Translation::SCOPE_SYSTEM)
        );

        $translation = $this->repository->findTranslation($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);
    }

    public function testUpdateScopeUIValue()
    {
        $key = LoadTranslations::TRANSLATION_KEY_3;
        $value = uniqid('TEST_VALUE', true);
        $locale = LoadLanguages::LANGUAGE1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $this->createTranslation($key, 'initial value', $locale, $domain, Translation::SCOPE_INSTALLED);

        // Ensure That We Overwrite SCOPE_INSTALLED
        $this->assertNotNull($this->manager->saveTranslation($key, $value, $locale, $domain, Translation::SCOPE_UI));
        $this->manager->flush();

        $translation = $this->repository->findTranslation($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);

        //Ensure That We cannot Overwrite SCOPE_UI
        $this->assertNull(
            $this->manager->saveTranslation($key, uniqid('', true), $locale, $domain, Translation::SCOPE_INSTALLED)
        );
        $translation = $this->repository->findTranslation($key, $locale, $domain);
        $this->ensureTranslationIsCorrect($translation, $key, $value, $domain, $locale);
    }

    public function testDeleteTranslation()
    {
        $key = LoadTranslations::TRANSLATION_KEY_3;
        $value = null;
        $locale = LoadLanguages::LANGUAGE2;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        $this->createTranslation($key, 'initial value', $locale, $domain, Translation::SCOPE_UI);

        $this->assertNull($this->manager->saveTranslation($key, $value, $locale, $domain, Translation::SCOPE_UI));
        $this->manager->flush();

        $translation = $this->repository->findTranslation($key, $locale, $domain);
        $this->assertNull($translation);
    }

    public function testFindTranslationKey()
    {
        $key = LoadTranslations::TRANSLATION_KEY_1;
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        /* @var $repository TranslationKeyRepository */
        $repository = $this->getRepository(TranslationKey::class);

        $existingKey = $repository->findOneBy(['key' => $key, 'domain' => $domain]);

        $this->assertNotNull($existingKey);

        $this->assertSame($existingKey, $this->manager->findTranslationKey($key, $domain));
    }

    public function testCreateTranslationKey()
    {
        $key = 'translation.key1';
        $domain = LoadTranslations::TRANSLATION_KEY_DOMAIN;

        /* @var $repository TranslationKeyRepository */
        $repository = $this->getRepository(TranslationKey::class);

        $this->assertNull($repository->findOneBy(['key' => $key, 'domain' => $domain]));

        $translationKey = $this->manager->findTranslationKey($key, $domain);

        $this->manager->flush();

        $this->assertSame($translationKey, $repository->findOneBy(['key' => $key, 'domain' => $domain]));
        $this->assertEquals($key, $translationKey->getKey());
    }

    public function testRemoveTranslationKey()
    {
        /* @var $repository TranslationKeyRepository */
        $repository = $this->getRepository(TranslationKey::class);

        $this->assertNotNull(
            $repository->findOneBy([
                'key' => LoadTranslations::TRANSLATION_KEY_3,
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN
            ])
        );

        $this->manager->removeTranslationKey(
            LoadTranslations::TRANSLATION_KEY_3,
            LoadTranslations::TRANSLATION_KEY_DOMAIN
        );
        $this->manager->flush();

        $this->assertNull(
            $repository->findOneBy([
                'key' => LoadTranslations::TRANSLATION_KEY_3,
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN
            ])
        );
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $locale
     * @param string $domain
     * @param int $scope
     */
    protected function createTranslation($key, $value, $locale, $domain, $scope)
    {
        $this->manager->saveTranslation($key, $value, $locale, $domain, $scope);
        $this->manager->flush();
    }

    /**
     * @param string $class
     * @return EntityRepository
     */
    protected function getRepository($class)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass($class)
            ->getRepository($class);
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
