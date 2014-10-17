<?php

namespace Oro\Bundle\EntityPaginationBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;

class EntityPaginationListener
{
    const ENTITY_PAGINATION_PATH = '[options][entity_pagination]';
    const TOTAL_RECORDS_PATH     = '[options][totalRecords]';

    /**
     * @var EntityPaginationStorage
     */
    protected $storage;

    /** @var  DoctrineHelper */
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
        $paginationState = [];
        $dataGrid   = $event->getDatagrid();
        $dataSource = $dataGrid->getDatasource();

        if ($dataGrid->getConfig()->offsetGetByPath(self::ENTITY_PAGINATION_PATH) === true) {
            $queryBuilder = $dataSource->getQueryBuilder();

            $entityName = $queryBuilder->getRootEntities()[0];
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
            $paginationState['total']       = $result->offsetGetByPath(self::TOTAL_RECORDS_PATH);
            $paginationState['previous_id'] = null;
            $paginationState['next_id']     = null;

            $this->storage->addData($entityName, $gridName, $paginationState);
        }
    }
}
