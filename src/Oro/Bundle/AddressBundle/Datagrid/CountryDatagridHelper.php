<?php

namespace Oro\Bundle\AddressBundle\Datagrid;

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DataGridBundle\Event\ResultBefore;

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
     * @param ResultBefore $event
     */
    public function onResultBefore(ResultBefore $event)
    {
        $event->getQuery()->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\Translatable\Query\TreeWalker\TranslationWalker'
        );
    }
}
