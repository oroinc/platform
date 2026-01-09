<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;

/**
 * Enriches attribute group datagrid records with attribute labels.
 *
 * This listener processes datagrid results for attribute groups, fetching and adding the labels of all
 * attributes contained in each group to the result records, providing users with a comprehensive view
 * of group contents in the datagrid.
 */
class AttributeGroupGridListener
{
    /**
     * @var AttributeManager
     */
    protected $attributeManager;

    public function __construct(AttributeManager $attributeManager)
    {
        $this->attributeManager = $attributeManager;
    }

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
