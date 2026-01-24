<?php

namespace Oro\Bundle\EntityConfigBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator for the GroupAttributes constraint.
 *
 * This validator ensures that attributes are not duplicated across groups within an attribute family
 * and that all system attributes required by the entity are included in the family's groups, maintaining
 * data integrity and completeness of attribute families.
 */
class GroupAttributesValidator extends ConstraintValidator
{
    const ALIAS = 'oro_entity_config.validator.group_attributes';

    /**
     * @var AttributeManager
     */
    protected $attributeManager;

    public function __construct(AttributeManager $attributeManager)
    {
        $this->attributeManager = $attributeManager;
    }

    #[\Override]
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
