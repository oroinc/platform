<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * The validator for ValidOrganization constraint.
 */
class ValidOrganizationValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     * @param AbstractAddress $entity
     * @param ValidOrganization $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value) {
            return ;
        }

        if (!$value instanceof AbstractAddress) {
            throw new UnexpectedTypeException($value, AbstractAddress::class);
        }

        $ownerOrganizationId = $this->getOwnerOrganizationId($value);
        $systemOrganizationId = $this->getSystemOrganizationId($value);

        if ($ownerOrganizationId && $systemOrganizationId && $ownerOrganizationId != $systemOrganizationId) {
            $this->context->addViolation($constraint->message);
        }
    }

    private function getOwnerOrganizationId(AbstractAddress $address)
    {
        if (!$frontendOwner = $address->getFrontendOwner()) {
            return null;
        }

        if ($organization = $frontendOwner->getOrganization()) {
            return $organization->getId();
        }

        return null;
    }

    private function getSystemOrganizationId(AbstractAddress $address)
    {
        if ($systemOrganization = $address->getSystemOrganization()) {
            return $systemOrganization->getId();
        }

        return null;
    }
}
