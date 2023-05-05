<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Oro\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * The validator for ValidOrganization constraint.
 */
class ValidOrganizationValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidOrganization) {
            throw new UnexpectedTypeException($constraint, ValidOrganization::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof AbstractDefaultTypedAddress) {
            throw new UnexpectedTypeException($value, AbstractDefaultTypedAddress::class);
        }

        $systemOrganizationId = $value->getSystemOrganization()?->getId();
        if (null === $systemOrganizationId) {
            return;
        }
        $ownerOrganizationId = $value->getFrontendOwner()?->getOrganization()?->getId();
        if (null === $ownerOrganizationId) {
            return;
        }

        if ($ownerOrganizationId !== $systemOrganizationId) {
            $this->context->addViolation($constraint->message);
        }
    }
}
