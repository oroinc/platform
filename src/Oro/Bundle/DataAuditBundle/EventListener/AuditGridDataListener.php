<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Entity\Repository\AuditFieldRepository;
use Oro\Bundle\DataAuditBundle\Model\FieldsTransformer;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Listener to fill data column for audit grids using a separate single query
 */
class AuditGridDataListener
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var FieldsTransformer
     */
    private $fieldsTransformer;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param FieldsTransformer $fieldsTransformer
     */
    public function __construct(DoctrineHelper $doctrineHelper, FieldsTransformer $fieldsTransformer)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->fieldsTransformer = $fieldsTransformer;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function addDataSupport(OrmResultAfter $event)
    {
        $ids = [];
        $records = $event->getRecords();
        if (!$records) {
            return;
        }

        foreach ($records as $record) {
            $id = $record->getValue('id');
            $ids[$id] = $id;
        }

        /** @var AuditFieldRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(AuditField::class);
        $fields = $repository->getVisibleFieldsByAuditIds($ids);
        if (!$fields) {
            return;
        }

        foreach ($records as $record) {
            $id = $record->getValue('id');
            if (!array_key_exists($id, $fields)) {
                continue;
            }
            $field = $fields[$id];
            $data = $this->fieldsTransformer->getData($field);
            $record->setValue('data', $data);
        }
    }
}
