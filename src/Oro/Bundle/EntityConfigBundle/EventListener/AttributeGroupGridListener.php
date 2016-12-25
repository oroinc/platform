<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;

class AttributeGroupGridListener
{
    /**
     * @var AttributeManager
     */
    protected $attributeManager;

    /**
     * @param AttributeManager $attributeManager
     */
    public function __construct(AttributeManager $attributeManager)
    {
        $this->attributeManager = $attributeManager;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $groupIds = [];
        foreach ($records as $record) {
            $groupIds[] = $record->getValue('id');
        }

        $attributeMap = $this->attributeManager->getAttributesMapByGroupIds($groupIds);
        $attributeIds = [];
        foreach ($attributeMap as $ids) {
            $attributeIds = array_merge($attributeIds, $ids);
        }
        $attributes = $this->attributeManager->getAttributesByIdsWithIndex($attributeIds);

        foreach ($records as $record) {
            $groupAttributeIds = isset($attributeMap[$record->getValue('id')])
                ? $attributeMap[$record->getValue('id')]
                : [];
            $labels = [];
            if (!empty($groupAttributeIds)) {
                foreach ($groupAttributeIds as $id) {
                    if (empty($attributes[$id])) {
                        continue;
                    }
                    $labels[] = $this->attributeManager->getAttributeLabel($attributes[$id]);
                }
            }
            $record->addData(['attributes' => $labels]);
        }
    }
}
