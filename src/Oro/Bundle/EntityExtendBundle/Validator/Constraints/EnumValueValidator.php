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
        if (!$entity instanceof EnumValueEntity) {
            throw new UnexpectedTypeException(
                $entity,
                'Oro\Bundle\EntityExtendBundle\Model\EnumValue'
            );
        }

        /* @var $entity EnumValueEntity */
        if ($entity->getId() || !$entity->getLabel()) {
            return;
        }

        $valueId = ExtendHelper::buildEnumValueId($entity->getLabel(), false);

        if (empty($valueId)) {
            $this->context->addViolationAt('label', $constraint->message, ['{{ value }}' => $entity->getLabel()]);
        }
    }
}
