<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
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
        $queryBuilder = $this->getTranslationQueryBuilder($locale, $domain);
        $queryBuilder
            ->select('tk.key, t.value')
            ->andWhere(
                $queryBuilder->expr()->like('tk.key', ':keysPrefix')
            )
            ->setParameter('keysPrefix', $keysPrefix . '%', Types::STRING);

        return array_column(
            $queryBuilder->getQuery()->getArrayResult(),
            'value',
            'key'
        );
    }

    /**
     * @param string $key
     * @param string $locale
     * @param string $domain
     * @return Translation|null
     */
    public function findTranslation($key, $locale, $domain)
    {
        $queryBuilder = $this->getTranslationQueryBuilder($locale, $domain);

        return $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->eq('tk.key', ':key')
            )
            ->setParameter('key', $key, Types::STRING)
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
        $qb = $this->getTranslationQueryBuilder($languageCode);
        $qb
            ->distinct()
            ->select('t.id, t.value, tk.key, tk.domain, l.code');

        if (count($scopes)) {
            $qb
                ->andWhere($qb->expr()->in('t.scope', ':scopes'))
                ->setParameter('scopes', $scopes);
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

    /**
     * @param string $languageCode
     * @param string $domain
     *
     * @return array [['key' => '...', 'value' => '...'], ...]
     */
    public function findDomainTranslations(string $languageCode, string $domain): array
    {
        $qb = $this->getTranslationQueryBuilder($languageCode, $domain);
        $qb
            ->distinct()
            ->select('tk.key, t.value')
            ->andWhere($qb->expr()->gte('t.scope', ':scope'))
            ->setParameter('scope', Translation::SCOPE_SYSTEM, Types::INTEGER);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param string $key
     * @param string|null $domain
     * @param string|null $locale
     * @return array [['key' => '...', 'value' => '...'], ...]
     */
    public function findTranslations(string $key, ?string $domain = null, ?string $locale = null): array
    {
        $qb = $this->getTranslationQueryBuilder($locale, $domain);
        $qb
            ->distinct()
            ->select('tk.key', 't.value')
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->like('tk.key', ':key'),
                    $qb->expr()->eq('t.scope', ':scope')
                )
            )
            ->setParameter('scope', Translation::SCOPE_SYSTEM, Types::INTEGER)
            ->setParameter('key', $key . '%', Types::STRING);

        return array_column(
            $qb->getQuery()->getArrayResult(),
            'value',
            'key'
        );
    }

    private function getTranslationQueryBuilder(?string $locale, ?string $domain = null): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            ->join('t.language', 'l')
            ->join('t.translationKey', 'tk');

        if ($locale) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('l.code', ':locale'))
                ->setParameter('locale', $locale, Types::STRING);
        }

        if ($domain) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('tk.domain', ':domain'))
                ->setParameter('domain', $domain, Types::STRING);
        }

        return $queryBuilder;
    }
}
