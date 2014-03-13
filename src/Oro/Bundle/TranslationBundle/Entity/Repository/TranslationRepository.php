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
     * Update existing translation value or create new one if it does not exist
     *
     * @param string $key
     * @param string $value
     * @param string $locale
     * @param string $domain
     * @param int    $scope
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
    }

    /**
     * Renames a translation key
     *
     * @param string $oldKey
     * @param string $newKey
     * @param string $domain
     * @return bool if a translation key exists and it was renamed
     */
    public function renameKey(
        $oldKey,
        $newKey,
        $domain = self::DEFAULT_DOMAIN
    ) {
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
}
