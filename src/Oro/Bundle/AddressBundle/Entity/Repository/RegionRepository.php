<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

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
        return $this->createQueryBuilder('r')
            ->where('r.country = :country')
            ->orderBy('r.name', 'ASC')
            ->setParameter('country', $country);
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
