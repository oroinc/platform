<?php

namespace Oro\Bundle\EntityConfigBundle\Layout;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;

class AttributeGroupRenderRegistry
{
    /** @var array */
    private $renderedGroupsByFamily = [];

    /**
     * @param AttributeFamily $attributeFamily
     * @param AttributeGroup  $attributeGroup
     */
    public function setRendered(AttributeFamily $attributeFamily, AttributeGroup $attributeGroup)
    {
        $this->renderedGroupsByFamily[$attributeFamily->getCode()][$attributeGroup->getCode()] = true;
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @param AttributeGroup  $attributeGroup
     * @return bool
     */
    public function isRendered(AttributeFamily $attributeFamily, AttributeGroup $attributeGroup)
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
}
