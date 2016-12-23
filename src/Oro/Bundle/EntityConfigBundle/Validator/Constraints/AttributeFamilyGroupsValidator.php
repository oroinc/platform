<?php

namespace Oro\Bundle\EntityConfigBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AttributeFamilyGroupsValidator extends ConstraintValidator
{
    const ALIAS = 'oro_entity_config.validator.attribute_family_groups';

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof AttributeFamily) {
            return;
        }

        /** @var \Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup[] $attributeGroups */
        $attributeGroups = $value->getAttributeGroups()->getValues();

        if (!count($attributeGroups)) {
            $this->context->addViolation($constraint->emptyGroupsMessage);

            return;
        }
    }
}
