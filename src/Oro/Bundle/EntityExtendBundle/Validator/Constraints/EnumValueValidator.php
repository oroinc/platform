<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\EntityExtendBundle\Model\EnumValue as EnumValueEntity;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($entity, Constraint $constraint)
    {
        if ($entity instanceof EnumValueEntity) {
            $entity = $entity->toArray();
        }

        if (!is_array($entity)) {
            throw new UnexpectedTypeException(
                $entity,
                'Oro\Bundle\EntityExtendBundle\Model\EnumValue|array'
            );
        }

        if (!empty($entity['id']) || empty($entity['label'])) {
            return;
        }

        $valueId = ExtendHelper::buildEnumValueId($entity['label'], false);

        if (empty($valueId)) {
            $this->context
                ->addViolationAt('[label]', $constraint->message, ['{{ value }}' => $entity['label']]);
        }
    }
}
