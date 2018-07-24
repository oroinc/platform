<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\DataProvider;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeRenderRegistry;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

/**
 * Layout data provider which returns options for attribute groups for the use in attribute_group_rest block type.
 */
class AttributeGroupsTabsOptionsProvider
{
    /**
     * @var AttributeRenderRegistry
     */
    private $attributeRenderRegistry;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @param AttributeRenderRegistry $attributeRenderRegistry
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        AttributeRenderRegistry $attributeRenderRegistry,
        LocalizationHelper $localizationHelper
    ) {
        $this->attributeRenderRegistry = $attributeRenderRegistry;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param AttributeFamily $attributeFamily
     *
     * @return array
     */
    public function getTabsOptions(AttributeFamily $attributeFamily)
    {
        $groups = $this->attributeRenderRegistry->getNotRenderedGroups($attributeFamily);
        $tabListOptions = array_map(
            function (AttributeGroup $group) {
                return [
                    'id' => $group->getCode(),
                    'label' => (string)$this->localizationHelper->getLocalizedValue($group->getLabels())
                ];
            },
            $groups->toArray()
        );

        return array_values($tabListOptions);
    }
}
