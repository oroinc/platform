<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class NameOrOrganizationValidator extends ConstraintValidator
{
    const ALIAS = 'oro_address.validator.name_or_organization';

    /**
     * {@inheritdoc}
     * @param NameOrOrganization $constraint
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$entity instanceof AbstractAddress) {
            throw new UnexpectedTypeException($entity, 'AbstractAddress');
        }
        if ((!$entity->getFirstName() || !$entity->getLastName()) && !$entity->getOrganization()) {
            // organization or (first name and last name) should be filled
            $this->context->addViolationAt('firstName', $constraint->firstNameMessage);
            $this->context->addViolationAt('lastName', $constraint->lastNameMessage);
            $this->context->addViolationAt('organization', $constraint->organizationMessage);
        }
    }
}
