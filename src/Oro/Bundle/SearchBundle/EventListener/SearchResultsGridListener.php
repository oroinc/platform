<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

/**
 * Handles "from" and "search" parameters for search datagrids.
 */
class SearchResultsGridListener
{
    private Indexer $indexer;
    private FeatureChecker $featureChecker;

    public function __construct(Indexer $indexer, FeatureChecker $featureChecker)
    {
        $this->indexer = $indexer;
        $this->featureChecker = $featureChecker;
    }

    public function onBuildAfter(BuildAfter $event): void
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof SearchDatasource) {
            $searchQuery = $datasource->getSearchQuery();

            $parameters = $datagrid->getParameters();
            $searchEntity = $parameters->get('from');
            if ($searchEntity) {
                $searchQuery->setFrom($searchEntity);
            } elseif (!$searchQuery->getFrom()) {
                $searchQuery->setFrom($this->getAllowedEntities());
            }

            $searchString = $parameters->get('search', '');
            if ($searchString) {
                $searchQuery->addWhere(Criteria::expr()->contains(Indexer::TEXT_ALL_DATA_FIELD, $searchString));
            }
        }
    }

    private function getAllowedEntities(): array
    {
        $entities = $this->indexer->getAllowedEntitiesListAliases();
        foreach (array_keys($entities) as $entityClass) {
            if (!$this->featureChecker->isResourceEnabled($entityClass, 'entities')) {
                unset($entities[$entityClass]);
            }
        }

        return array_values($entities);
    }
}
