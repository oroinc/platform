<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TranslationBundle\Translation\TranslatableQueryTrait;
use Oro\Component\DoctrineUtils\ORM\Walker\TranslatableSqlWalker;

/**
 * The repository for Region entity.
 */
class RegionRepository extends EntityRepository
{
    use TranslatableQueryTrait;

    public function getCountryRegionsQueryBuilder(Country $country): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r');

        return $qb
            ->where($qb->expr()->eq('r.country', ':country'))
            ->andWhere($qb->expr()->eq('r.deleted', ':deleted'))
            ->orderBy('r.name', 'ASC')
            ->setParameter('country', $country)
            ->setParameter('deleted', false, Types::BOOLEAN);
    }

    /**
     * @param Country $country
     *
     * @return Region[]
     */
    public function getCountryRegions(Country $country): array
    {
        $query = $this->getCountryRegionsQueryBuilder($country)->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslatableSqlWalker::class);
        $this->addTranslatableLocaleHint($query, $this->getEntityManager());

        return $query->execute();
    }
}
