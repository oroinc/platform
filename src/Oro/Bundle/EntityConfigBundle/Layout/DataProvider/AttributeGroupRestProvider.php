<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\DataProvider;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeGroupRenderRegistry;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class AttributeGroupRestProvider
{
    /** @var array */
    private $options = [];

    /** @var AttributeGroupRenderRegistry */
    private $attributeGroupRenderRegistry;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /**
     * @param AttributeGroupRenderRegistry $attributeGroupRenderRegistry
     * @param LocalizationHelper           $localizationHelper
     */
    public function __construct(
        AttributeGroupRenderRegistry $attributeGroupRenderRegistry,
        LocalizationHelper $localizationHelper
    ) {
        $this->attributeGroupRenderRegistry = $attributeGroupRenderRegistry;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param AttributeFamily $attributeFamily
     *
     * @return array
     */
    public function getTabsOptions(AttributeFamily $attributeFamily)
    {
        if (!array_key_exists('tabsOptions', $this->options)) {
            $groups = $this->attributeGroupRenderRegistry->getNotRenderedGroups($attributeFamily);
            $tabListOptions = array_map(
                function (AttributeGroup $group) {
                    return [
                        'id' => $group->getCode(),
                        'label' => (string) $this->localizationHelper->getLocalizedValue($group->getLabels())
                    ];
                },
                $groups->toArray()
            );

            $this->options['tabsOptions'] = [
                'data' => array_values($tabListOptions)
            ];
        }

        return $this->options['tabsOptions'];
    }
}
