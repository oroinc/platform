<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;

class AttributesDatagridListener
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $attributeIds = [];
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        foreach ($records as $record) {
            $attributeIds[] = $record->getValue('id');
        }

        /** @var AttributeGroupRelationRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(AttributeGroupRelation::class);

        $families = $repository->getFamiliesLabelsByAttributeIds($attributeIds);
        foreach ($records as $record) {
            $record->setValue('attributeFamilies', $families[$record->getValue('id')]);
        }
    }
}
