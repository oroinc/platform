<?php

namespace Oro\Bundle\EntityPaginationBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Datagrid\EntityPaginationExtension;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;

class EntityPaginationListener
{
    /** @var EntityPaginationStorage  */
    protected $storage;

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /**
     * @param EntityPaginationStorage $storage
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(EntityPaginationStorage $storage, DoctrineHelper $doctrineHelper)
    {
        $this->storage        = $storage;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        if (!$this->storage->isEnabled()) {
            return;
        }

        $paginationState = [];
        $dataGrid = $event->getDatagrid();

        if ($dataGrid->getConfig()->offsetGetByPath(EntityPaginationExtension::ENTITY_PAGINATION_PATH) === true) {
            /** @var OrmDatasource $dataSource */
            $dataSource = $dataGrid->getDatasource();
            $queryBuilder = $dataSource->getQueryBuilder();

            $entityName = $queryBuilder->getRootEntities()[0];

            $entityName = $this->doctrineHelper->getEntityMetadata($entityName)->getName();
            $gridName   = $dataGrid->getName();

            $paginationState['state'] = $dataGrid->getParameters()->all();

            $records = $event->getRecords();
            $entityIdentifier = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityName);
            $paginationState['current_ids'] = [];
            foreach ($records as $record) {
                $paginationState['current_ids'][] = $record->getValue($entityIdentifier);
            }

            $result = ResultsObject::create([]);
            $dataGrid->getAcceptor()->acceptResult($result);
            $paginationState['total'] = $result->offsetGetByPath(PagerInterface::TOTAL_PATH_PARAM);

            $this->storage->addData($entityName, $gridName, $paginationState);
        }
    }
}
