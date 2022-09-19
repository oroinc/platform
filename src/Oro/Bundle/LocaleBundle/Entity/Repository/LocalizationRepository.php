<?php

namespace Oro\Bundle\LocaleBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorInterface;
use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Doctrine repository for Localization entity
 *
 * @method Localization|null findOneByName($name)
 */
class LocalizationRepository extends EntityRepository implements BatchIteratorInterface
{
    use BatchIteratorTrait;

    /**
     * @return array
     */
    public function getNames()
    {
        $qb = $this->createQueryBuilder('l');

        return $qb
            ->select('l.name')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @return int
     */
    public function getLocalizationsCount()
    {
        return (int)$this->createQueryBuilder('l')
            ->select('COUNT(l.id) as localizationsCount')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneByLanguageCodeAndFormattingCode(string $languageCode, string $formattingCode): ?Localization
    {
        $qb = $this->createQueryBuilder('localization');

        return $qb->innerJoin('localization.language', 'language')
            ->where(
                $qb->expr()->eq('localization.formattingCode', ':formattingCode'),
                $qb->expr()->eq('language.code', ':languageCode')
            )
            ->setParameter('formattingCode', $formattingCode)
            ->setParameter('languageCode', $languageCode)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllIndexedById(): array
    {
        return $this
            ->createQueryBuilder('localization', 'localization.id')
            ->orderBy('localization.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
