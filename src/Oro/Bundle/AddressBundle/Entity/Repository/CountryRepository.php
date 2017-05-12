<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class CountryRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function getAllCountryNamesArray()
    {
        return $this->createQueryBuilder('c')
            ->select('c.iso2Code, c.iso3Code, c.name')
            ->getQuery()
            ->getArrayResult();
    }
}
