<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\TagBundle\Security\SecurityProvider;

class TagSearchResultsGridListener
{
    /** @var string */
    protected $paramName;

    /** @var SecurityProvider  */
    protected $securityProvider;

    /**
     * @param SecurityProvider $securityProvider
     */
    public function __construct(SecurityProvider $securityProvider)
    {
        $this->securityProvider = $securityProvider;
    }

    /**
     * Adjust query for tag-results-grid (tag search result grid)
     * after datasource has been built
     *
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $parameters = $datagrid->getParameters();
            $queryBuilder = $datasource->getQueryBuilder();

            $this->securityProvider->applyAcl($queryBuilder, 'tt');

            $queryBuilder->setParameter('tag', $parameters->get('tag_id', 0));

            $searchEntity = $parameters->get('from', '*');
            if ($searchEntity != '*' && !empty($searchEntity)) {
                $queryBuilder->andWhere('tt.alias = :alias')
                    ->setParameter('alias', $searchEntity);
            }
        }
    }
}
