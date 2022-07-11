<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Component\DoctrineUtils\ORM\Walker\TranslatableSqlWalker;

/**
 * The repository for Country entity.
 */
class CountryRepository extends EntityRepository
{
    /**
     * @return Country[]
     */
    public function getCountries(): array
    {
        $query = $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslatableSqlWalker::class);

        return $query->execute();
    }
}
