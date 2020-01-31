<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\ExportPreGetIds;
use Oro\Bundle\ImportExportBundle\Reader\BatchIdsReaderInterface;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader;
use Oro\Bundle\ImportExportBundle\Reader\ReaderInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Reader for export filtered entities.
 */
class FilteredEntityReader implements ReaderInterface, BatchIdsReaderInterface
{
    const FILTERED_RESULTS_GRID = 'filteredResultsGrid';

    /** @var Manager */
    private $datagridManager;

    /** @var AclHelper */
    private $aclHelper;

    /** @var EntityReader */
    private $entityReader;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param Manager $datagridManager
     * @param AclHelper $aclHelper
     * @param EntityReader $entityReader
     */
    public function __construct(Manager $datagridManager, AclHelper $aclHelper, EntityReader $entityReader)
    {
        $this->datagridManager = $datagridManager;
        $this->aclHelper = $aclHelper;
        $this->entityReader = $entityReader;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->entityReader->setStepExecution($stepExecution);
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        return $this->entityReader->read();
    }

    /**
     * {@inheritdoc}
     */
    public function getIds($entityName, array $options = [])
    {
        if (!isset($options['filteredResultsGrid'], $options['filteredResultsGridParams'])) {
            return $this->entityReader->getIds($entityName, $options);
        }

        $qb = $this->getQueryBuilder($options['filteredResultsGrid'], $options['filteredResultsGridParams']);

        $event = new ExportPreGetIds($qb, $options);
        $this->eventDispatcher->dispatch(Events::BEFORE_EXPORT_GET_IDS, $event);

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
     * @param string $gridName
     * @param string $queryString
     * @return QueryBuilder
     */
    private function getQueryBuilder(string $gridName, string $queryString): QueryBuilder
    {
        parse_str($queryString, $parameters);

        // Creates grid based on parameters from query string
        $datagrid = $this->datagridManager->getDatagrid($gridName, [ParameterBag::MINIFIED_PARAMETERS => $parameters]);

        /** @var OrmDatasource $datasource */
        $datasource = $datagrid->getAcceptedDatasource();

        return $datasource->getQueryBuilder()
            ->setFirstResult(null)
            ->setMaxResults(null);
    }
}
