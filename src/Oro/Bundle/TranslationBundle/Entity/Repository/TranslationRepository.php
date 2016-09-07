<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class TranslationRepository extends EntityRepository
{
    const DEFAULT_DOMAIN = 'messages';

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
     * @param string $locale
     *
     * @return int
     */
    public function getCountByLocale($locale)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('count(t.id)')
            ->where('t.locale = :locale')
            ->setParameter('locale', $locale);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string $locale
     */
    public function deleteByLocale($locale)
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.locale = :locale')
            ->setParameter('locale', $locale)
            ->getQuery()
            ->execute();
    }
}
