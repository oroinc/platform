<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamilyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

/**
 * Add attributes to view for entity view and edit pages.
 */
class AttributeFormViewListener
{
    /**
     * @var AttributeManager
     */
    private $attributeManager;

    /**
     * @param AttributeManager $attributeManager
     */
    public function __construct(AttributeManager $attributeManager)
    {
        $this->attributeManager = $attributeManager;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEdit(BeforeListRenderEvent $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof AttributeFamilyAwareInterface) {
            return;
        }

        $scrollData = $event->getScrollData();
        $formView = $event->getFormView();
        $groupsData = $this->attributeManager->getGroupsWithAttributes($entity->getAttributeFamily());
        $this->filterGroupAttributes($groupsData, 'form', 'is_enabled');
        $this->addNotEmptyGroupBlocks($scrollData, $groupsData);

        foreach ($groupsData as $groupsDatum) {
            /** @var AttributeGroup $group */
            $group = $groupsDatum['group'];
            /** @var FieldConfigModel $attribute */
            foreach ($groupsDatum['attributes'] as $attribute) {
                $fieldId = $attribute->getFieldName();
                $attributeView = $formView->offsetGet($fieldId);

                if (!$attributeView->isRendered()) {
                    $html = $event->getEnvironment()->render('OroEntityConfigBundle:Attribute:row.html.twig', [
                        'child' => $attributeView,
                    ]);

                    $subblockId = $scrollData->addSubBlock($group->getCode());
                    $scrollData->addSubBlockData($group->getCode(), $subblockId, $html, $fieldId);
                } else {
                    $this->moveFieldToBlock($scrollData, $attribute->getFieldName(), $group->getCode());
                }
            }
        }

        $this->removeEmptyGroupBlocks($scrollData, $groupsData);
    }

    /**
     * @param ScrollData $scrollData
     * @param array $groups
     */
    private function removeEmptyGroupBlocks(ScrollData $scrollData, array $groups)
    {
        $data = $scrollData->getData();
        if (empty($data[ScrollData::DATA_BLOCKS])) {
            return;
        }

        foreach ($data[ScrollData::DATA_BLOCKS] as $blockId => $data) {
            if (!is_string($blockId)) {
                continue;
            }
            $isEmpty = true;
            if (!empty($data[ScrollData::SUB_BLOCKS])) {
                foreach ($data[ScrollData::SUB_BLOCKS] as $subblockId => $subblockData) {
                    if (!empty($subblockData[ScrollData::DATA])) {
                        $isEmpty = false;
                    }
                }
            }

            if ($isEmpty) {
                $scrollData->removeNamedBlock($blockId);
            }
        }
    }

    /**
     * @param ScrollData $scrollData
     * @param array $groups
     */
    private function addNotEmptyGroupBlocks(ScrollData $scrollData, array $groups)
    {
        foreach ($groups as $group) {
            if (!empty($group['attributes'])) {
                /** @var AttributeGroup $currentGroup */
                $currentGroup = $group['group'];
                $scrollData->addNamedBlock($currentGroup->getCode(), $currentGroup->getLabel()->getString());
            }
        }
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onViewList(BeforeListRenderEvent $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof AttributeFamilyAwareInterface) {
            return;
        }

        $groups = $this->attributeManager->getGroupsWithAttributes($entity->getAttributeFamily());
        $scrollData = $event->getScrollData();
        $this->filterGroupAttributes($groups, 'view', 'is_displayable');
        $this->addNotEmptyGroupBlocks($scrollData, $groups);

        /** @var AttributeGroup $group */
        foreach ($groups as $groupData) {
            /** @var AttributeGroup $group */
            $group = $groupData['group'];

            /** @var FieldConfigModel $attribute */
            foreach ($groupData['attributes'] as $attribute) {
                $fieldName = $attribute->getFieldName();
                if ($scrollData->hasNamedField($fieldName)) {
                    $this->moveFieldToBlock($scrollData, $fieldName, $group->getCode());
                    continue;
                }

                $html = $event->getEnvironment()->render(
                    'OroEntityConfigBundle:Attribute:attributeView.html.twig',
                    [
                        'entity' => $entity,
                        'field' => $attribute,
                    ]
                );

                $subblockId = $scrollData->addSubBlock($group->getCode());
                $scrollData->addSubBlockData($group->getCode(), $subblockId, $html, $fieldName);
            }
        }

        $this->removeEmptyGroupBlocks($scrollData, $groups);
    }

    /**
     * @param ScrollData $scrollData
     * @param string $fieldName
     * @param string $blockId
     */
    protected function moveFieldToBlock(ScrollData $scrollData, $fieldName, $blockId)
    {
        $scrollData->moveFieldToBlock($fieldName, $blockId);
    }

    /**
     * @param array $groups
     * @param string $scope
     * @param string $option
     */
    private function filterGroupAttributes(array &$groups, $scope, $option)
    {
        foreach ($groups as &$group) {
            $group['attributes'] = array_filter(
                $group['attributes'],
                function (FieldConfigModel $attribute) use ($scope, $option) {
                    $attributeScopedConfig = $attribute->toArray($scope);
                    return !empty($attributeScopedConfig[$option]);
                }
            );
        }
    }
}
