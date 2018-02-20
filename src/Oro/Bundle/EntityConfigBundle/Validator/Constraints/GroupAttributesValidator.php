<?php

namespace Oro\Bundle\EntityConfigBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class GroupAttributesValidator extends ConstraintValidator
{
    const ALIAS = 'oro_entity_config.validator.group_attributes';

    /**
     * @var AttributeManager
     */
    protected $attributeManager;

    /**
     * @param AttributeManager $attributeManager
     */
    public function __construct(AttributeManager $attributeManager)
    {
        $this->attributeManager = $attributeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof AttributeFamily) {
            return;
        }

        /** @var AttributeGroup[] $attributeGroups */
        $attributeGroups = $value->getAttributeGroups()->getValues();

        $attributeIds = [];
        foreach ($attributeGroups as $group) {
            /** @var AttributeGroupRelation $relation */
            foreach ($group->getAttributeRelations() as $relation) {
                $attributeIds[] = $relation->getEntityConfigFieldId();
            }
        }
        if (count($attributeIds) !== count(array_unique($attributeIds))) {
            $this->context->addViolation($constraint->duplicateAttributesMessage);

            return;
        }

        $systemAttributeIds = [];
        $systemAttributes = $this->attributeManager->getSystemAttributesByClass($value->getEntityClass());
        foreach ($systemAttributes as $attribute) {
            $systemAttributeIds[] = $attribute->getId();
        }

        $diff = array_diff($systemAttributeIds, $attributeIds);
        if ($diff) {
            $this->context->addViolation($constraint->missingSystemAttributesMessage);
        }
    }
}
