<?php

namespace Oro\Bundle\EntityConfigBundle\Layout;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class AttributeRenderRegistry
{
    /** @var array */
    private $renderedGroupsByFamily = [];

    /** @var array */
    private $renderedAttributesByFamily = [];

    /**
     * @param AttributeFamily $attributeFamily
     * @param AttributeGroup  $attributeGroup
     */
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
