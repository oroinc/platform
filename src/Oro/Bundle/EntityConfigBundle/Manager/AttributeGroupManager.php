<?php

namespace Oro\Bundle\EntityConfigBundle\Manager;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Manager for working with attributes groups
 */
class AttributeGroupManager
{
    /** @var ConfigManager */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param string $entityClassName
     * @param array $groups
     * @return AttributeGroup[]
     */
    public function createGroupsWithAttributes(string $entityClassName, array $groups): array
    {
        $attributeGroups = [];
        foreach ($groups as $groupData) {
            if (!isset($groupData['groupLabel'], $groupData['groupVisibility'], $groupData['groupCode'])) {
                continue;
            }

            $attributeGroup = new AttributeGroup();
            $attributeGroup->setDefaultLabel($groupData['groupLabel']);
            $attributeGroup->setIsVisible($groupData['groupVisibility']);
            $attributeGroup->setCode($groupData['groupCode']);
            foreach ($groupData['attributes'] as $attribute) {
                $fieldConfigModel = $this->configManager->getConfigFieldModel($entityClassName, $attribute);
                if ($fieldConfigModel) {
                    $attributeGroupRelation = new AttributeGroupRelation();
                    $attributeGroupRelation->setEntityConfigFieldId($fieldConfigModel->getId());
                    $attributeGroup->addAttributeRelation($attributeGroupRelation);
                }
            }
            $attributeGroups[] = $attributeGroup;
        }

        return $attributeGroups;
    }
}
