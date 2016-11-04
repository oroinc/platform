<?php

namespace Oro\Bundle\TranslationBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

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

    /** @var TranslationKey[] */
    protected $translationKeysToRemove = [];

    /** @var Translation[] */
    protected $translations = [];

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
     *
     * @return Translation
     */
    public function createTranslation($key, $value, $locale, $domain = self::DEFAULT_DOMAIN)
    {
        $cacheKey = $this->getCacheKey($locale, $domain, $key);
        if (!array_key_exists($cacheKey, $this->translations)) {
            $translationValue = new Translation();
            $translationValue
                ->setTranslationKey($this->findTranslationKey($key, $domain))
                ->setLanguage($this->getLanguageByCode($locale))
                ->setValue($value);

            $this->translations[$cacheKey] = $translationValue;
        }

        return $this->translations[$cacheKey];
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
    public function saveTranslation(
        $key,
        $value,
        $locale,
        $domain = self::DEFAULT_DOMAIN,
        $scope = Translation::SCOPE_SYSTEM
    ) {
        /** @var TranslationRepository $repo */
        $repo = $this->getEntityRepository(Translation::class);

        $this->findTranslationKey($key, $domain);

        $translation = $repo->findTranslation($key, $locale, $domain);
        if (!$this->canUpdateTranslation($scope, $translation)) {
            return null;
        }

        if (!$value && null !== $translation) {
            $cacheKey = $this->getCacheKey($locale, $domain, $key);
            $this->translations[$cacheKey] = $translation;

            return null;
        }

        if ($value && null === $translation) {
            $translation = $this->createTranslation($key, $value, $locale, $domain);
        }

        if (null !== $translation) {
            $translation->setValue($value);
            $translation->setScope($scope);

            $cacheKey = $this->getCacheKey($locale, $domain, $key);
            $this->translations[$cacheKey] = $translation;
        }

        return $translation;
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
            }

            $this->translationKeys[$cacheKey] = $translationKey;
        }

        return $this->translationKeys[$cacheKey];
    }

    /**
     * Remove Translation Key
     *
     * @param string $key
     * @param string $domain
     */
    public function removeTranslationKey($key, $domain)
    {
        $translationKey = $this->getEntityRepository(TranslationKey::class)
            ->findOneBy(['key' => $key, 'domain' => $domain]);

        if ($translationKey) {
            $cacheKey = sprintf('%s-%s', $domain, $key);
            $this->translationKeys[$cacheKey] = $translationKey;
            $this->translationKeysToRemove[$cacheKey] = $translationKey;
        }
    }

    /**
     * @param string $keysPrefix
     * @param string $domain
     */
    public function removeTranslationKeysByPrefix($keysPrefix, $domain)
    {
        $queryBuilder = $this->getEntityRepository(TranslationKey::class)->createQueryBuilder('tk');
        $queryBuilder->delete()
            ->where('tk.domain = :domain')
            ->andWhere($queryBuilder->expr()->like('tk.key', ':keysPrefix'))
            ->setParameters(['domain' => $domain,  'keysPrefix' => $keysPrefix . '%'])
            ->getQuery()
            ->execute();
    }

    /**
     * @param int $scope
     * @param Translation|null $translation
     * @return bool
     */
    protected function canUpdateTranslation($scope, Translation $translation = null)
    {
        return null === $translation || $translation->getScope() <= $scope;
    }

    /**
     * Flushes all changes
     *
     * @param bool|false $force
     */
    public function flush($force = false)
    {
        $em = $this->getEntityManager(TranslationKey::class);
        foreach ($this->translationKeys as $translationKey) {
            $em->persist($translationKey);
        }

        foreach ($this->translationKeysToRemove as $translationKey) {
            $em->remove($translationKey);
        }

        $em = $this->getEntityManager(Translation::class);
        foreach ($this->translations as $translation) {
            if (!$translation->getValue()) {
                $em->remove($translation);
            } else {
                $em->persist($translation);
            }
        }

        if ($force) {
            $em->flush();
        } else {
            $entities = array_merge(
                array_values($this->translationKeys),
                array_values($this->translationKeysToRemove),
                array_values($this->translations)
            );

            if ($entities) {
                $em->flush($entities);
            }
        }

        $this->clear();
    }

    public function clear()
    {
        $this->languages = [];
        $this->translationKeys = [];
        $this->translationKeysToRemove = [];
        $this->translations = [];
        $this->availableDomains = null;
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
     * @return EntityRepository
     */
    protected function getEntityRepository($class)
    {
        return $this->getEntityManager($class)->getRepository($class);
    }

    /**
     * @param string $locale
     * @param string $domain
     * @param string $key
     * @return string
     */
    private function getCacheKey($locale, $domain, $key)
    {
        return sprintf('%s-%s-%s', $locale, $domain, $key);
    }
}
