<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;

/**
 * The repository for Translation entity.
 */
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
            ->setParameter('code', $locale, Types::STRING)
            ->setParameter('key', $key, Types::STRING)
            ->setParameter('domain', $domain, Types::STRING)
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

    public function findAllByLanguageAndScopes(
        string $languageCode,
        array $scopes = []
    ) {
        $qb = $this->createQueryBuilder('t');
        $qb->distinct()
            ->select('t.id, t.value, k.key, k.domain, l.code')
            ->join('t.language', 'l')
            ->join('t.translationKey', 'k')
            ->setParameter('code', $languageCode);

        if (count($scopes)) {
            $qb->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('l.code', ':code'),
                    $qb->expr()->in('t.scope', ':scopes'),
                )
            )
                ->setParameter('scopes', $scopes);
        } else {
            $qb->where($qb->expr()->eq('l.code', ':code'));
        }

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
