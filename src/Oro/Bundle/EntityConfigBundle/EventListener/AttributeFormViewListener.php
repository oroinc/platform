<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\AttributeFilter\AttributesMovingFilterInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamilyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

class AttributeFormViewListener
{
    /**
     * @var AttributeManager
     */
    private $attributeManager;

    /**
     * @var AttributesMovingFilterInterface
     */
    private $attributeFormViewFilter;

    /**
     * @var AttributeFamilyAwareInterface
     */
    private $entity;

    /**
     * @param AttributeManager $attributeManager
     * @param AttributesMovingFilterInterface|null $filter
     */
    public function __construct(
        AttributeManager $attributeManager,
        AttributesMovingFilterInterface $filter = null
    ) {
        $this->attributeManager = $attributeManager;
        $this->attributeFormViewFilter = $filter;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEdit(BeforeListRenderEvent $event)
    {
        if (!$this->entity) {
            return;
        }

        $scrollData = $event->getScrollData();
        $formView = $event->getFormView();
        $groupsData = $this->attributeManager->getGroupsWithAttributes($this->entity->getAttributeFamily());
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
                        'child' => $attributeView
                    ]);

                    $subblockId = $scrollData->addSubBlock($group->getCode());
                    $scrollData->addSubBlockData($group->getCode(), $subblockId, $html, $fieldId);
                } else {
                    $scrollData->moveFieldToBlock($attribute->getFieldName(), $group->getCode());
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

    public function onViewList(BeforeListRenderEvent $event)
    {
        if (!$this->entity) {
            return;
        }

        $groups = $this->attributeManager->getGroupsWithAttributes($this->entity->getAttributeFamily());
        $scrollData = $event->getScrollData();
        $this->addNotEmptyGroupBlocks($scrollData, $groups);

        /** @var AttributeGroup $group */
        foreach ($groups as $groupData) {
            /** @var AttributeGroup $group */
            $group = $groupData['group'];

            /** @var FieldConfigModel $attribute */
            foreach ($groupData['attributes'] as $attribute) {
                $fieldName = $attribute->getFieldName();
                if ($scrollData->hasNamedField($fieldName)) {
                    if (!$this->attributeFormViewFilter ||
                        !$this->attributeFormViewFilter->isRestrictedToMove($fieldName)
                    ) {
                        $scrollData->moveFieldToBlock($fieldName, $group->getCode());
                    }
                    continue;
                }

                $html = $event->getEnvironment()->render(
                    'OroEntityConfigBundle:Attribute:attributeView.html.twig',
                    [
                        'entity' => $this->entity,
                        'field' => $attribute
                    ]
                );

                $subblockId = $scrollData->addSubBlock($group->getCode());
                $scrollData->addSubBlockData($group->getCode(), $subblockId, $html, $fieldName);
            }
        }

        $this->removeEmptyGroupBlocks($scrollData, $groups);
    }

    /**
     * @param BeforeFormRenderEvent $event
     */
    public function onFormRender(BeforeFormRenderEvent $event)
    {
        $this->setEntity($event->getEntity());
    }

    /**
     * @param BeforeViewRenderEvent $event
     */
    public function onViewRender(BeforeViewRenderEvent $event)
    {
        $this->setEntity($event->getEntity());
    }

    /**
     * @param object $entity
     */
    private function setEntity($entity)
    {
        if ($entity instanceof AttributeFamilyAwareInterface) {
            $this->entity = $entity;
        }
    }
}
