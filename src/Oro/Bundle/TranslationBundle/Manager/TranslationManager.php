<?php

namespace Oro\Bundle\TranslationBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
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
        return $this->getEntityRepository(Translation::class)->findOneBy(
            [
                'language' => $this->getLanguageByCode($locale),
                'domain' => $domain,
                'key' => $key,
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
        return $this->getEntityRepository(Translation::class)->findBy(
            [
                'language' => $this->getLanguageByCode($locale),
                'domain' => $domain,
            ]
        );
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
        $translationValue = $this->findValue($key, $locale, $domain, $scope);
        if (!$translationValue) {
            $translationValue = new Translation();
            $translationValue
                ->setKey($key)
                ->setLanguage($this->getLanguageByCode($locale))
                ->setDomain($domain)
                ->setScope($scope);
        }

        $translationValue->setValue($value);

        $this->getEntityManager(Translation::class)->persist($translationValue);

        return $translationValue;
    }

    /**
     * @param string $locale
     *
     * @return int
     */
    public function getCountByLocale($locale)
    {
        /** @var TranslationRepository $repo */
        $repo = $this->getEntityRepository(Translation::class);

        return $repo->getCountByLanguage($this->getLanguageByCode($locale));
    }

    /**
     * @param string $locale
     */
    public function deleteByLocale($locale)
    {
        /** @var TranslationRepository $repo */
        $repo = $this->getEntityRepository(Translation::class);
        $repo->deleteByLocale($locale);
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
     * @param $code
     *
     * @return Language|null
     */
    protected function getLanguageByCode($code)
    {
        /** @var LanguageRepository $repo */
        $repo = $this->getEntityRepository(Language::class);

        return $repo->findOneBy(['code' => $code]);
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
     * @param $class
     *
     * @return ObjectRepository
     */
    protected function getEntityRepository($class)
    {
        return $this->getEntityManager($class)->getRepository($class);
    }
}
