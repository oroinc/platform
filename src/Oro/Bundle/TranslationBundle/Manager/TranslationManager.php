<?php

namespace Oro\Bundle\TranslationBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
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

    /** @var array */
    protected $availableDomains;

    /** @var Language[] */
    protected $languages = [];

    /** @var TranslationKey[] */
    protected $translationKeys = [];

    /** @var Translation[] */
    protected $createdTranslationValues = [];

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
     * @param string $key
     * @param string $value
     * @param string $locale
     * @param string $domain
     * @param bool $persist
     *
     * @return Translation
     */
    public function createValue(
        $key,
        $value,
        $locale,
        $domain = self::DEFAULT_DOMAIN,
        $persist = false
    ) {
        $cacheKey = sprintf('%s-%s-%s', $locale, $domain, $key);
        if (!array_key_exists($cacheKey, $this->createdTranslationValues)) {
            $translationValue = new Translation();
            $translationValue
                ->setTranslationKey($this->findTranslationKey($key, $domain))
                ->setLanguage($this->getLanguageByCode($locale))
                ->setValue($value);

            $this->createdTranslationValues[$cacheKey] = $translationValue;
        }

        if ($persist) {
            $this->getEntityManager(Translation::class)->persist($this->createdTranslationValues[$cacheKey]);
        }

        return $this->createdTranslationValues[$cacheKey];
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
     * @return Translation|null
     */
    public function saveValue($key, $value, $locale, $domain = self::DEFAULT_DOMAIN, $scope = Translation::SCOPE_SYSTEM)
    {
        /** @var TranslationRepository $repo */
        $repo = $this->getEntityRepository(Translation::class);

        $translationValue = $repo->findValue($key, $locale, $domain);
        if (!$this->canUpdateTranslation($scope, $translationValue)) {
            return null;
        }

        if (!$value && $translationValue) {
            $this->getEntityManager(Translation::class)->remove($translationValue);
            return null;
        }

        if (null === $translationValue) {
            $translationValue = $this->createValue($key, $value, $locale, $domain, true);
        }

        $translationValue->setValue($value);
        $translationValue->setScope($scope);

        return $translationValue;
    }

    /**
     * @param int $scope
     * @param Translation $translation
     * @return bool
     */
    protected function canUpdateTranslation($scope, Translation $translation = null)
    {
        return null === $translation || $translation->getScope() <= $scope;
    }

    /**
     * Flushes all changes
     */
    public function flush()
    {
        $this->getEntityManager(Translation::class)->flush();

        // clear local cache
        $this->languages = [];
        $this->translationKeys = [];
        $this->createdTranslationValues = [];
        $this->availableDomains = null;
    }

    public function clear()
    {
        $this->getEntityManager(Translation::class)->clear();
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
        if (null === $this->availableDomains) {
            /** @var TranslationKeyRepository $repo */
            $repo = $this->getEntityRepository(TranslationKey::class);

            $this->availableDomains = $repo->findAvailableDomains();
        }

        $result = [];
        foreach ($locales as $locale) {
            foreach ($this->availableDomains as $domain) {
                $result[] = [
                    'code' => $locale,
                    'domain' => $domain,
                ];
            }
        }

        return $result;
    }

    /**
     * Returns the list of all existing in the database translation domains
     *
     * @return array
     */
    public function getAvailableDomains()
    {
        /** @var TranslationKeyRepository $repo */
        $repo = $this->getEntityRepository(TranslationKey::class);

        return $repo->findAvailableDomains();
    }

    /**
     * @param string $code
     *
     * @return Language|null
     */
    protected function getLanguageByCode($code)
    {
        if (!array_key_exists($code, $this->languages)) {
            /** @var LanguageRepository $repo */
            $repo = $this->getEntityRepository(Language::class);

            $this->languages[$code] = $repo->findOneBy(['code' => $code]);
        }

        return $this->languages[$code];
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
        $cacheKey = sprintf('%s-%s', $domain, $key);
        if (!array_key_exists($cacheKey, $this->translationKeys)) {
            $translationKey = $this->getEntityRepository(TranslationKey::class)
                ->findOneBy(['key' => $key, 'domain' => $domain]);

            if (!$translationKey) {
                $translationKey = new TranslationKey();
                $translationKey->setKey($key);
                $translationKey->setDomain($domain);

                $this->getEntityManager(TranslationKey::class)->persist($translationKey);
            }

            $this->translationKeys[$cacheKey] = $translationKey;
        }

        return $this->translationKeys[$cacheKey];
    }

    /**
     * @param string|null $locale
     */
    public function invalidateCache($locale = null)
    {
        $this->dbTranslationMetadataCache->updateTimestamp($locale);
    }

    /**
     * @param string $class
     *
     * @return EntityManager|null
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
