<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\TagBundle\Security\SecurityProvider;

/**
 * Handles the configuration of tag search results grid after datasource construction.
 *
 * This listener adjusts the query for the tag-results-grid by applying security ACL rules
 * and filtering results based on the requested tag and source entity type. It ensures that
 * only authorized tag search results are displayed to the user.
 */
class TagSearchResultsGridListener
{
    /** @var SecurityProvider */
    protected $securityProvider;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    public function __construct(SecurityProvider $securityProvider, EntityAliasResolver $entityAliasResolver)
    {
        $this->securityProvider    = $securityProvider;
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * Adjust query for tag-results-grid (tag search result grid)
     * after datasource has been built
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid   = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $parameters   = $datagrid->getParameters();
            $queryBuilder = $datasource->getQueryBuilder();

            $this->securityProvider->applyAcl($queryBuilder, 'tt');

            $queryBuilder->setParameter('tag', $parameters->get('tag_id', 0));

            $from = $parameters->get('from', '');
            if (strlen($from) > 0) {
                try {
                    $entityClass = $this->entityAliasResolver->getClassByAlias($from);
                    $queryBuilder->andWhere('tt.entityName = :entityClass')
                        ->setParameter('entityClass', $entityClass);
                } catch (EntityAliasNotFoundException $e) {
                    $queryBuilder->andWhere('1 = 0');
                }
            }
        }
    }
}
