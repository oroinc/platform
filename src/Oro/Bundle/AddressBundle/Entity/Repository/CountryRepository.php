<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;

use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;

use Oro\Bundle\AddressBundle\Entity\Country;

class CountryRepository extends EntityRepository
{
    /**
     * @return Country[]
     */
    public function getCountries()
    {
        $query = $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery();

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);

        return $query->execute();
    }
}
