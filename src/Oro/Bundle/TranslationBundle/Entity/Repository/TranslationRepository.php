<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;

class TranslationRepository extends EntityRepository
{
    /**
     * @param string $keysPrefix
     * @param string $locale
     * @param string $domain
     * @return array
     */
    public function findValues($keysPrefix, $locale, $domain)
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $result = $queryBuilder->select('tk.key, t.value')
            ->join('t.language', 'l')
            ->join('t.translationKey', 'tk')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('tk.domain', ':domain'),
                    $queryBuilder->expr()->eq('l.code', ':locale'),
                    $queryBuilder->expr()->like('tk.key', ':keysPrefix')
                )
            )
            ->setParameters(['locale' => $locale, 'domain' => $domain, 'keysPrefix' => $keysPrefix . '%'])
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'value', 'key');
    }

    /**
     * @param string $key
     * @param string $locale
     * @param string $domain
     * @return Translation|null
     */
    public function findTranslation($key, $locale, $domain)
    {
        $queryBuilder = $this->createQueryBuilder('t');

        return $queryBuilder->join('t.language', 'l')
            ->join('t.translationKey', 'k')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('l.code', ':code'),
                    $queryBuilder->expr()->eq('k.key', ':key'),
                    $queryBuilder->expr()->eq('k.domain', ':domain')
                )
            )
            ->setParameter('code', $locale, Type::STRING)
            ->setParameter('key', $key, Type::STRING)
            ->setParameter('domain', $domain, Type::STRING)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Language $language
     *
     * @return int
     */
    public function getCountByLanguage(Language $language)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.language = :language')
            ->setParameter('language', $language);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Language $language
     */
    public function deleteByLanguage($language)
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.language = :language')
            ->setParameter('language', $language)
            ->getQuery()
            ->execute();
    }

    /**
     * @param string $languageCode
     * @param string $domain
     *
     * @return array [['id' => '...', 'value' => '...', 'key' => '...', 'domain' => '...', 'code' => '...'], ...]
     */
    public function findAllByLanguageAndDomain($languageCode, $domain)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->distinct(true)
            ->select('t.id, t.value, k.key, k.domain, l.code')
            ->join('t.language', 'l')
            ->join('t.translationKey', 'k')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('l.code', ':code'),
                    $qb->expr()->gt('t.scope', ':scope'),
                    $qb->expr()->eq('k.domain', ':domain')
                )
            )
            ->setParameter('code', $languageCode)
            ->setParameter('domain', $domain, Type::STRING)
            ->setParameter('scope', Translation::SCOPE_SYSTEM);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param int $languageId
     * @return array
     */
    public function getTranslationsData($languageId)
    {
        $translationsData = $this->createQueryBuilder('t')
            ->select('IDENTITY(t.translationKey) as translation_key_id, t.scope, t.value')
            ->where('t.language = :language')
            ->setParameters(['language' => $languageId])
            ->getQuery()
            ->getArrayResult();
        $translations = [];
        foreach ($translationsData as $item) {
            $translations[$item['translation_key_id']] = $item;
        }

        return $translations;
    }
}
