<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NameOrOrganizationValidator extends ConstraintValidator
{
    const ALIAS = 'oro_address.validator.name_or_organization';

    /**
     * {@inheritdoc}
     * @param NameOrOrganization $constraint
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$entity) {
            return;
        }
        if (!$entity instanceof AbstractAddress) {
            throw new UnexpectedTypeException($entity, 'AbstractAddress');
        }
        if ((!$entity->getFirstName() || !$entity->getLastName()) && !$entity->getOrganization()) {
            // organization or (first name and last name) should be filled
            $this->context->buildViolation($constraint->firstNameMessage)
                ->atPath('firstName')
                ->addViolation();
            $this->context->buildViolation($constraint->lastNameMessage)
                ->atPath('lastName')
                ->addViolation();
            $this->context->buildViolation($constraint->organizationMessage)
                ->atPath('organization')
                ->addViolation();
        }
    }
}
