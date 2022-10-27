<?php

namespace Oro\Bundle\TranslationBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Async\Topic\DumpJsTranslationsTopic;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationCache;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Provides functionality to manage translations that are stored in the database.
 */
class TranslationManager
{
    public const DEFAULT_DOMAIN = 'messages';

    private ManagerRegistry $doctrine;
    private TranslationDomainProvider $domainProvider;
    private DynamicTranslationCache $dynamicTranslationCache;
    private MessageProducerInterface $producer;
    /** @var string[] */
    private array $jsTranslationDomains;

    /** @var Language[] */
    private array $languages = [];
    /** @var TranslationKey[] */
    private array $translationKeys = [];
    /** @var TranslationKey[] */
    private array $translationKeysToRemove = [];
    /** @var Translation[] */
    private array $translations = [];

    public function __construct(
        ManagerRegistry $doctrine,
        TranslationDomainProvider $domainProvider,
        DynamicTranslationCache $dynamicTranslationCache,
        MessageProducerInterface $producer,
        array $jsTranslationDomains
    ) {
        $this->doctrine = $doctrine;
        $this->domainProvider = $domainProvider;
        $this->dynamicTranslationCache = $dynamicTranslationCache;
        $this->producer = $producer;
        $this->jsTranslationDomains = $jsTranslationDomains;
    }

    /**
     * Creates a new translation entity object.
     */
    public function createTranslation(
        string $key,
        string $value,
        string $locale,
        string $domain = self::DEFAULT_DOMAIN
    ): Translation {
        $translation = new Translation();
        $translation->setTranslationKey($this->findTranslationKey($key, $domain));
        $translation->setLanguage($this->getLanguageByCode($locale));
        $translation->setValue($value);

        return $translation;
    }

    /**
     * Updates existing translation value or create new one if it does not exist.
     */
    public function saveTranslation(
        string $key,
        ?string $value,
        string $locale,
        string $domain = self::DEFAULT_DOMAIN,
        int $scope = Translation::SCOPE_SYSTEM
    ): ?Translation {
        /** @var TranslationRepository $repo */
        $repo = $this->getEntityRepository(Translation::class);

        $this->findTranslationKey($key, $domain);

        $translation = $repo->findTranslation($key, $locale, $domain);
        if (!$this->canUpdateTranslation($scope, $translation)) {
            return null;
        }

        $cacheKey = $this->getCacheKey($locale, $domain, $key);

        if (null === $value && null !== $translation) {
            $translation->setValue($value);
            $this->translations[$cacheKey] = $translation;
            return null;
        }

        if (null !== $value && null === $translation) {
            $translation = \array_key_exists($cacheKey, $this->translations)
                ? $this->translations[$cacheKey]
                : $this->createTranslation($key, $value, $locale, $domain);
        }

        if (null !== $translation) {
            $this->translations[$cacheKey] = $translation->setValue($value)->setScope($scope);
        }

        return $translation;
    }

    /**
     * Tries to find a translation key or creates new one if it is not found.
     */
    public function findTranslationKey(string $key, string $domain = self::DEFAULT_DOMAIN): TranslationKey
    {
        $cacheKey = sprintf('%s-%s', $domain, $key);
        if (!\array_key_exists($cacheKey, $this->translationKeys)) {
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
     * Remove a translation key.
     */
    public function removeTranslationKey(string $key, string $domain): void
    {
        $translationKey = $this->getEntityRepository(TranslationKey::class)
            ->findOneBy(['key' => $key, 'domain' => $domain]);

        if ($translationKey) {
            $cacheKey = sprintf('%s-%s', $domain, $key);
            $this->translationKeys[$cacheKey] = $translationKey;
            $this->translationKeysToRemove[$cacheKey] = $translationKey;
        }
    }

    private function canUpdateTranslation(int $scope, Translation $translation = null): bool
    {
        return null === $translation || $translation->getScope() <= $scope;
    }

    /**
     * Flushes all changes and dumps JS translations if they are changed.
     */
    public function flush(bool $force = false): void
    {
        $em = $this->getEntityManager(Translation::class);
        [$locales, $domains] = $this->prepareData($em);
        $this->flushData($em, $force, $locales);
        if ($this->hasJsTranslations($domains)) {
            $this->producer->send(DumpJsTranslationsTopic::getName(), []);
        }
    }

    /**
     * Flushes all changes without dumping JS translations.
     */
    public function flushWithoutDumpJsTranslations(bool $force = false): bool
    {
        $em = $this->getEntityManager(Translation::class);
        [$locales, $domains] = $this->prepareData($em);
        $this->flushData($em, $force, $locales);

        return $this->hasJsTranslations($domains);
    }

    private function prepareData(EntityManager $em): array
    {
        foreach ($this->translationKeys as $translationKey) {
            $em->persist($translationKey);
        }

        foreach ($this->translationKeysToRemove as $key => $translationKey) {
            if ($translationKey->getId()) {
                $em->remove($translationKey);
            } else {
                unset($this->translationKeysToRemove[$key]);
            }
        }

        $locales = [];
        $domains = [];
        foreach ($this->translations as $key => $translation) {
            if (null !== $translation->getValue()) {
                $em->persist($translation);
            } elseif ($translation->getId()) {
                $em->remove($translation);
            } else {
                unset($this->translations[$key]);
            }
            $locale = $this->extractLocaleFromCacheKey($key);
            if (!isset($locales[$locale])) {
                $locales[$locale] = true;
            }
            $domain = $translation->getTranslationKey()->getDomain();
            if (!isset($domains[$domain])) {
                $domains[$domain] = true;
            }
        }
        $locales = array_keys($locales);
        $domains = array_keys($domains);

        return [$locales, $domains];
    }

    private function flushData(EntityManager $em, bool $force, array $locales): void
    {
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
        $this->clearDynamicTranslationCache($locales);
    }

    public function clear(): void
    {
        $this->languages = [];
        $this->translationKeys = [];
        $this->translationKeysToRemove = [];
        $this->translations = [];
        $this->domainProvider->clearCache();
    }

    private function getLanguageByCode(string $code): ?Language
    {
        if (!\array_key_exists($code, $this->languages)) {
            $this->languages[$code] = $this->getEntityRepository(Language::class)->findOneBy(['code' => $code]);
        }

        return $this->languages[$code];
    }

    public function invalidateCache(string $locale): void
    {
        $this->clearDynamicTranslationCache([$locale]);
    }

    private function getEntityManager(string $class): EntityManager
    {
        return $this->doctrine->getManagerForClass($class);
    }

    private function getEntityRepository(string $class): EntityRepository
    {
        return $this->getEntityManager($class)->getRepository($class);
    }

    private function getCacheKey(string $locale, string $domain, string $key): string
    {
        return sprintf('%s-%s-%s', $locale, $domain, $key);
    }

    private function extractLocaleFromCacheKey(string $cacheKey): string
    {
        return substr($cacheKey, 0, strpos($cacheKey, '-'));
    }

    private function clearDynamicTranslationCache(array $locales): void
    {
        if ($locales) {
            $this->dynamicTranslationCache->delete($locales);
        }
    }

    private function hasJsTranslations(array $domains): bool
    {
        if ($domains && $this->jsTranslationDomains) {
            foreach ($domains as $domain) {
                if (\in_array($domain, $this->jsTranslationDomains, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
