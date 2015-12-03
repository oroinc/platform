<?php

namespace Oro\Bundle\AddressBundle\Datagrid;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

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

    /**
     * Set country translation query walker
     *
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $source = $event->getDatagrid()->getDatasource();
        if ($source instanceof OrmDatasource) {
            $source->getQueryBuilder()->getQuery()->setHint(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\Translatable\Query\TreeWalker\TranslationWalker'
            );
        }
    }
}
