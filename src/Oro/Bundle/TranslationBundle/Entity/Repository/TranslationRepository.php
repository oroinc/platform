<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TranslationBundle\Entity\Translation;

class TranslationRepository extends EntityRepository
{
    const DEFAULT_DOMAIN = 'messages';

    /**
     * @param string $key
     * @param string $locale
     * @param string $domain
     * @param int    $scope
     *
     * @return Translation
     */
    public function findValue($key, $locale, $domain = self::DEFAULT_DOMAIN, $scope = Translation::SCOPE_SYSTEM)
    {
        return $this->findOneBy(
            [
                'locale' => $locale,
                'domain' => $domain,
                'key'    => $key,
                'scope'  => $scope
            ]
        );
    }

    /**
     * @param        $locale
     * @param string $domain
     *
     * @return Translation[]
     */
    public function findValues($locale, $domain = self::DEFAULT_DOMAIN)
    {
        return $this->findBy(
            [
                'locale' => $locale,
                'domain' => $domain
            ]
        );
    }

    /**
     * Returns the list of all existing in the database translation domains for the given locale.
     *
     * @param string $locale
     *
     * @return array
     */
    public function findAvailableDomains($locale)
    {
        $qb = $this->createQueryBuilder('t')
            ->distinct(true)
            ->select('t.domain')
            ->where('t.locale = :locale')
            ->setParameter('locale', $locale);

        $domains = $qb->getQuery()->getArrayResult();
        foreach ($domains as &$domain) {
            $domain = reset($domain);
        }

        return $domains;
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
        $qb = $this->createQueryBuilder('t')
            ->distinct(true)
            ->select('t.locale', 't.domain')
            ->where('t.locale IN (:locales)')
            ->setParameter('locales', $locales);

        return $qb->getQuery()->getArrayResult();
    }


    /**
     * Update existing translation value or create new one if it does not exist
     *
     * @param string $key
     * @param string $value
     * @param string $locale
     * @param string $domain
     * @param int    $scope
     *
     * @return Translation
     */
    public function saveValue(
        $key,
        $value,
        $locale,
        $domain = self::DEFAULT_DOMAIN,
        $scope = Translation::SCOPE_SYSTEM
    ) {
        $translationValue = $this->findValue($key, $locale, $domain, $scope);
        if (!$translationValue) {
            $translationValue = new Translation();
            $translationValue
                ->setKey($key)
                ->setValue($value)
                ->setLocale($locale)
                ->setDomain($domain)
                ->setScope($scope);
        } else {
            $translationValue->setValue($value);
        }
        $this->getEntityManager()->persist($translationValue);

        return $translationValue;
    }

    /**
     * Renames a translation key
     *
     * @param string $oldKey
     * @param string $newKey
     * @param string $domain
     * @return bool TRUE if a translation key exists and it was renamed
     */
    public function renameKey($oldKey, $newKey, $domain = self::DEFAULT_DOMAIN)
    {
        /** @var Translation[] $translationValues */
        $translationValues = $this->findBy(
            [
                'key' => $oldKey,
                'domain' => $domain
            ]
        );
        $result = false;
        foreach ($translationValues as $translationValue) {
            $translationValue->setKey($newKey);
            $this->getEntityManager()->persist($translationValue);
            $result = true;
        }

        return $result;
    }

    /**
     * Copies a translation value
     *
     * @param string $srcKey
     * @param string $destKey
     * @param string $domain
     * @return bool TRUE if a translation key exists and it was copied
     */
    public function copyValue($srcKey, $destKey, $domain = self::DEFAULT_DOMAIN)
    {
        /** @var Translation[] $srcTranslationValues */
        $srcTranslationValues = $this->findBy(
            [
                'key' => $srcKey,
                'domain' => $domain
            ]
        );
        /** @var Translation[] $destTranslationValues */
        $destTranslationValues = $this->findBy(
            [
                'key' => $destKey,
                'domain' => $domain
            ]
        );
        $result = false;
        foreach ($srcTranslationValues as $srcTranslationValue) {
            $destTranslationValue = null;
            foreach ($destTranslationValues as $val) {
                if ($val->getLocale() === $srcTranslationValue->getLocale()) {
                    $destTranslationValue = $val;
                    break;
                }
            }
            if (!$destTranslationValue) {
                $destTranslationValue = new Translation();
                $destTranslationValue
                    ->setKey($destKey)
                    ->setValue($srcTranslationValue->getValue())
                    ->setLocale($srcTranslationValue->getLocale())
                    ->setDomain($srcTranslationValue->getDomain())
                    ->setScope($srcTranslationValue->getScope());
            } else {
                $destTranslationValue->setValue($srcTranslationValue->getValue());
            }
            $this->getEntityManager()->persist($destTranslationValue);

            $result = true;
        }

        return $result;
    }
}
