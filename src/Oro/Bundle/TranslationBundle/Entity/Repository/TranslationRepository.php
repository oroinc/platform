<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\DBAL\Types\Type;
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
            ->setParameter('code', $locale, Type::STRING)
            ->setParameter('key', $key, Type::STRING)
            ->setParameter('domain', $domain, Type::STRING);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array [['code' = '...', 'domain' => '...'], ...]
     */
    public function findAvailableDomains()
    {
        $qb = $this->createQueryBuilder('t')
            ->distinct(true)
            ->select('l.code', 'k.domain')
            ->join('t.language', 'l')
            ->join('t.translationKey', 'k');

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
     * @param string $languageCode
     * @param string $domain
     *
     * @return array [['id' => '...', 'value' => '...', 'key' => '...', 'domain' => '...', 'code' => '...'], ...]
     */
    public function findAllByLanguageAndDomain($languageCode, $domain)
    {
        $qb = $this->createQueryBuilder('t')
            ->distinct(true)
            ->select('t.id, t.value, k.key, k.domain, l.code')
            ->join('t.language', 'l')
            ->join('t.translationKey', 'k')
            ->where('l.code = :code')
            ->andWhere('k.domain = :domain')
            ->setParameter('code', $languageCode)
            ->setParameter('domain', $domain, Type::STRING);

        return $qb->getQuery()->getArrayResult();
    }
}
