<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;

class TranslationRepository extends EntityRepository
{
    /**
     * @param string $key
     * @param string $locale
     * @param string $domain
     * @return Translation|null
     */
    public function findValue($key, $locale, $domain)
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.language', 'l')
            ->join('t.translationKey', 'k')
            ->where('l.code = :code AND k.key = :key AND k.domain = :domain')
            ->setParameter('code', $locale)
            ->setParameter('key', $key)
            ->setParameter('domain', $domain);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns the list of all existing in the database translation domains for the given locales.
     *
     * @param Language[] $languages
     *
     * @return array [['code' = '...', 'domain' => '...'], ...]
     */
    public function findAvailableDomainsForLocales(array $languages)
    {
        $qb = $this->createQueryBuilder('t')
            ->distinct(true)
            ->select('l.code', 'k.domain')
            ->join('t.language', 'l')
            ->join('t.translationKey', 'k');

        $qb->where($qb->expr()->in('l.code', $languages));

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param Language $language
     *
     * @return int
     */
    public function getCountByLanguage(Language $language)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('count(t.id)')
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
     * @param Language $language
     * @param string $domain
     *
     * @return Translation[]
     */
    public function findAllByLanguageAndDomain(Language $language, $domain)
    {
        $qb = $this->createQueryBuilder('t')
            ->distinct(true)
            ->select('t')
            ->join('t.language', 'l')
            ->join('t.translationKey', 'k')
            ->where('t.language = :language')
            ->andWhere('k.domain = :domain')
            ->setParameter('language', $language)
            ->setParameter('domain', $domain);

        return $qb->getQuery()->getResult();
    }
}
