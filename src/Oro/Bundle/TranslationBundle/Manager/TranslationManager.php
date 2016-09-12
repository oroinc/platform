<?php

namespace Oro\Bundle\TranslationBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class TranslationManager
{
    const DEFAULT_DOMAIN = 'messages';

    /** @var Registry */
    protected $registry;

    /** @var DynamicTranslationMetadataCache */
    protected $dbTranslationMetadataCache;

    /**
     * @param Registry $registry
     * @param DynamicTranslationMetadataCache $dbTranslationMetadataCache
     */
    public function __construct(Registry $registry, DynamicTranslationMetadataCache $dbTranslationMetadataCache)
    {
        $this->registry = $registry;
        $this->dbTranslationMetadataCache = $dbTranslationMetadataCache;
    }

    /**
     * @param $key
     * @param $locale
     * @param string $domain
     * @param int $scope
     *
     * @return Translation|null
     */
    public function findValue($key, $locale, $domain = self::DEFAULT_DOMAIN, $scope = Translation::SCOPE_SYSTEM)
    {
        /** @var TranslationRepository $repo */
        $repo = $this->getEntityRepository(Translation::class);

        return $repo->findOneBy(
            [
                'language' => $this->getLanguageByCode($locale),
                'translationKey' => $this->findTranslationKey($key, $domain),
                'scope' => $scope
            ]
        );
    }

    /**
     * Finds all translations for given locale and domain
     *
     * @param string $locale
     * @param string $domain
     *
     * @return Translation[]
     */
    public function findValues($locale, $domain = self::DEFAULT_DOMAIN)
    {
        /** @var TranslationRepository $repo */
        $repo = $this->getEntityRepository(Translation::class);

        return $repo->findAllByLanguageAndDomain($this->getLanguageByCode($locale), $domain);
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $locale
     * @param string $domain
     * @param int $scope
     *
     * @return Translation
     */
    public function createValue(
        $key,
        $value,
        $locale,
        $domain = self::DEFAULT_DOMAIN,
        $scope = Translation::SCOPE_SYSTEM
    ) {
        static $cache;
        if (!$cache) {
            $cache = [];
        }

        if (!isset($cache[$key . '-' . $locale . '-' . $domain])) {
            $translationValue = new Translation();
            $translationValue
                ->setTranslationKey($this->findTranslationKey($key, $domain))
                ->setLanguage($this->getLanguageByCode($locale))
                ->setScope($scope)
                ->setValue($value);
            $cache[$key . '-' . $locale . '-' . $domain] = $translationValue;
        }

        return $cache[$key . '-' . $locale . '-' . $domain];
    }

    /**
     * Update existing translation value or create new one if it does not exist
     *
     * @param string $key
     * @param string $value
     * @param string $locale
     * @param string $domain
     * @param int $scope
     *
     * @return Translation
     */
    public function saveValue($key, $value, $locale, $domain = self::DEFAULT_DOMAIN, $scope = Translation::SCOPE_SYSTEM)
    {
        if (null === ($translationValue = $this->findValue($key, $locale, $domain, $scope))) {
            $translationValue = $this->createValue($key, $value, $locale, $domain, $scope);
        }

        $translationValue->setValue($value);

        $this->getEntityManager(Translation::class)->persist($translationValue);

        return $translationValue;
    }

    /**
     * @param Language $language
     *
     * @return int
     */
    public function getCountByLanguage(Language $language)
    {
        return $this->getEntityRepository(Translation::class)->getCountByLanguage($language);
    }

    /**
     * @param Language $language
     */
    public function deleteByLanguage(Language $language)
    {
        return $this->getEntityRepository(Translation::class)->deleteByLanguage($language);
    }

    /**
     * @param Translation[]|null $translations
     */
    public function flush($translations = null)
    {
        $this->getEntityManager(Translation::class)->flush($translations);
    }

    /**
     * @param string|null $locale
     */
    public function invalidateCache($locale = null)
    {
        $this->dbTranslationMetadataCache->updateTimestamp($locale);
    }

    /**
     * Returns the list of all existing in the database translation domains for the given locales.
     *
     * @param string[] $locales
     *
     * @return array [['code' = '...', 'domain' => '...'], ...]
     */
    public function findAvailableDomainsForLocales(array $locales)
    {
        /** @var TranslationRepository $repo */
        $repo = $this->getEntityRepository(Translation::class);

        return $repo->findAvailableDomainsForLocales($locales);
    }

    /**
     * @param string $code
     *
     * @return Language|null
     */
    public function getLanguageByCode($code)
    {
        /** @var LanguageRepository $repo */
        $repo = $this->getEntityRepository(Language::class);

        return $repo->findOneBy(['code' => $code]);
    }

    /**
     * Tries to find Translation key and if not found creates new one
     *
     * @param string $key
     * @param string $domain
     *
     * @return TranslationKey
     */
    public function findTranslationKey($key, $domain = self::DEFAULT_DOMAIN)
    {
        /**
         * Required to avoid unique constraint
         */
        static $cache;
        if (!$cache) {
            $cache = [];
        }

        if (isset($cache[$key . '-' . $domain])) {
            return $cache[$key . '-' . $domain];
        }

        /** @var ObjectRepository $repo */
        $repo = $this->getEntityRepository(TranslationKey::class);

        $translationKey = $repo->findOneBy(['key' => $key, 'domain' => $domain]);

        if (!$translationKey) {
            $translationKey = new TranslationKey();
            $translationKey->setKey($key);
            $translationKey->setDomain($key);
            $this->getEntityManager(TranslationKey::class)->persist($translationKey);
        }

        $cache[$key . '-' . $domain] = $translationKey;

        return $cache[$key . '-' . $domain];
    }

    /**
     * @param string $class
     *
     * @return EntityManager|null|object
     */
    protected function getEntityManager($class)
    {
        return $this->registry->getManagerForClass($class);
    }

    /**
     * @param string $class
     *
     * @return ObjectRepository
     */
    protected function getEntityRepository($class)
    {
        return $this->getEntityManager($class)->getRepository($class);
    }
}
