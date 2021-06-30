<?php

namespace Oro\Bundle\DataGridBundle\ImportExport\FilteredEntityReader;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\ExportPreGetIds;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Entity identifiers reader. Uses Grid OrmDatasource to read identifiers.
 */
class OrmFilteredEntityIdentityReader implements FilteredEntityIdentityReaderInterface
{
    private EventDispatcherInterface $eventDispatcher;
    private AclHelper $aclHelper;

    public function __construct(EventDispatcherInterface $eventDispatcher, AclHelper $aclHelper)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getIds(DatagridInterface $datagrid, string $entityName, array $options): array
    {
        $datasource = $datagrid->getAcceptedDatasource();

        $qb = $datasource->getQueryBuilder()
            ->setFirstResult(null)
            ->setMaxResults(null);

        $event = new ExportPreGetIds($qb, $options);
        $this->eventDispatcher->dispatch($event, Events::BEFORE_EXPORT_GET_IDS);

        $identifier = $qb->getEntityManager()
            ->getClassMetadata($entityName)
            ->getSingleIdentifierFieldName();

        $query = $this->aclHelper->apply($qb);

        $filteredEntitiesIds = [];
        foreach (new BufferedQueryResultIterator($query) as $entity) {
            $filteredEntitiesIds[] = $entity[$identifier];
        }

        // Return "[0]" to prevent the export of all entities from the database
        return $filteredEntitiesIds ?: [0];
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridInterface $datagrid, string $className, array $options): bool
    {
        return $datagrid->getDatasource() instanceof OrmDatasource;
    }
}
