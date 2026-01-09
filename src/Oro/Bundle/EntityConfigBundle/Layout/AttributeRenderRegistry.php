<?php

namespace Oro\Bundle\EntityConfigBundle\Layout;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;

/**
 * Tracks which attribute groups and attributes have been rendered in layouts.
 *
 * This registry maintains state about which attribute groups and individual attributes have been rendered
 * for each attribute family, allowing layout blocks to determine which groups and attributes still need
 * to be rendered and avoiding duplicate rendering.
 */
class AttributeRenderRegistry
{
    /** @var array */
    private $renderedGroupsByFamily = [];

    /** @var array */
    private $renderedAttributesByFamily = [];

    public function setGroupRendered(AttributeFamily $attributeFamily, AttributeGroup $attributeGroup)
    {
        $this->renderedGroupsByFamily[$attributeFamily->getCode()][$attributeGroup->getCode()] = true;
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @param AttributeGroup  $attributeGroup
     * @return bool
     */
    public function isGroupRendered(AttributeFamily $attributeFamily, AttributeGroup $attributeGroup)
    {
        $familyCode = $attributeFamily->getCode();
        $groupCode = $attributeGroup->getCode();

        return isset(
            $this->renderedGroupsByFamily[$familyCode],
            $this->renderedGroupsByFamily[$familyCode][$groupCode]
        );
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @return ArrayCollection
     */
    public function getNotRenderedGroups(AttributeFamily $attributeFamily)
    {
        $familyCode = $attributeFamily->getCode();
        $renderedGroupCodes = [];
        if (isset($this->renderedGroupsByFamily[$familyCode])) {
            $renderedGroupCodes = array_keys($this->renderedGroupsByFamily[$familyCode]);
        }

        return $attributeFamily->getAttributeGroups()->filter(
            function (AttributeGroup $attributeGroup) use ($renderedGroupCodes) {
                $rendered = in_array($attributeGroup->getCode(), $renderedGroupCodes);

                return !$rendered && $attributeGroup->getIsVisible();
            }
        );
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @param string $attributeName
     */
    public function setAttributeRendered(AttributeFamily $attributeFamily, $attributeName)
    {
        $this->renderedAttributesByFamily[$attributeFamily->getCode()][$attributeName] = true;
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @param string $attributeName
     * @return bool
     */
    public function isAttributeRendered(AttributeFamily $attributeFamily, $attributeName)
    {
        $familyCode = $attributeFamily->getCode();

        return isset(
            $this->renderedAttributesByFamily[$familyCode],
            $this->renderedAttributesByFamily[$familyCode][$attributeName]
        );
    }
}
