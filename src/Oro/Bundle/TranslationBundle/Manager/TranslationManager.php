<?php

namespace Oro\Bundle\TranslationBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Validator\Constraints\Language;

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
                'locale' => $locale,
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
                'locale' => $locale,
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
    public function saveValue(
        $key,
        $value,
        $locale,
        $domain = self::DEFAULT_DOMAIN,
        $scope = Translation::SCOPE_SYSTEM
    )
    {
        $translationValue = $this->findValue($key, $locale, $domain, $scope);
        if (!$translationValue) {
            $translationValue = new Translation();
            $translationValue
                ->setKey($key)
                ->setLocale($locale)
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
        return $this->getEntityRepository(Translation::class)->getCountByLocale($locale);
    }

    /**
     * @param string $locale
     */
    public function deleteByLocale($locale)
    {
        $this->getEntityRepository(Translation::class)->deleteByLocale($locale);
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
     * @return array [['locale' = '...', 'domain' => '...'], ...]
     */
    public function findAvailableDomainsForLocales(array $locales)
    {
        return $this->getEntityRepository(Translation::class)->findAvailableDomainsForLocales($locales);
    }

    /**
     * @param $code
     *
     * @return Language|null
     */
    protected function getLanguageByCode($code)
    {
        return $this->getEntityRepository(Language::class)->findOneBy(['code' => $code]);
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
