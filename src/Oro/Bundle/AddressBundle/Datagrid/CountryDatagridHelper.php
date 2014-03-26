<?php

namespace Oro\Bundle\AddressBundle\Datagrid;

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;

class CountryDatagridHelper
{
    /**
     * Returns query builder callback for country filter form type
     *
     * @return callable
     */
    public function getCountryFilterQueryBuilder()
    {
        return function (EntityRepository $er) {
            return $er->createQueryBuilder('c')
                ->orderBy('c.name', 'ASC');
        };
    }
}
