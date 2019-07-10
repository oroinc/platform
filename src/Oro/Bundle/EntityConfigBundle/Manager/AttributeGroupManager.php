<?php

namespace Oro\Bundle\EntityConfigBundle\Manager;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Manager for working with attributes groups
 */
class AttributeGroupManager
{
    /** @var AttributeManager */
    private $attributeManager;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param AttributeManager $attributeManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        AttributeManager $attributeManager,
        TranslatorInterface $translator
    ) {
        $this->attributeManager = $attributeManager;
        $this->translator = $translator;
    }

    /**
     * @param string $className
     *
     * @return AttributeGroup
     */
    public function createGroupWithSystemAttributes($className)
    {
        $group = new AttributeGroup();
        $group->setDefaultLabel(
            $this->translator->trans('oro.entity_config.form.default_group_label')
        );
        $systemAttributes = $this->attributeManager->getSystemAttributesByClass($className);

        /** @var FieldConfigModel $systemAttribute */
        foreach ($systemAttributes as $systemAttribute) {
            $attributeGroupRelation = new AttributeGroupRelation();
            $attributeGroupRelation->setEntityConfigFieldId($systemAttribute->getId());

            $group->addAttributeRelation($attributeGroupRelation);
        }

        return $group;
    }
}
