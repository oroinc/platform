<?php

namespace Oro\Bundle\TestFrameworkBundle\Datagrid\Filter;

use Doctrine\ORM\EntityRepository;

/**
 * Class for sort taxonomies in choose filter
 */
final class ActionFilter
{
    public function addSorting()
    {
        return function (EntityRepository $er) {
            $qb = $er->createQueryBuilder('t');
            return $qb
                ->orderBy('t.name', 'DESC');
        };
    }
}
