<?php

namespace Oro\Bundle\SearchBundle\Datagrid\FilteredEntityReader;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\ImportExport\FilteredEntityReader\FilteredEntityIdentityReaderInterface;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchIterableResult;

/**
 * Entity Identifier reader. Read entity ids from Grid SearchDatasource.
 */
class SearchSourceFilteredEntityIdentityReader implements FilteredEntityIdentityReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getIds(DatagridInterface $datagrid, string $entityName, array $options): array
    {
        $datasource = $datagrid->getAcceptedDatasource();
        $query = $datasource->getSearchQuery()
            ->setFirstResult(null)
            ->setMaxResults(null);

        $filteredEntitiesIds = [];

        $iterator = new SearchIterableResult($query);
        $iterator->setBufferSize(1000);

        foreach ($iterator as $entity) {
            $filteredEntitiesIds[] = $entity->getRecordId();
        }

        return $filteredEntitiesIds ?: [0];
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridInterface $datagrid, string $className, array $options): bool
    {
        return $datagrid->getDatasource() instanceof SearchDatasource;
    }
}
