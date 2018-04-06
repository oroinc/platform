<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator that checks that parent for the business unit is not his child.
 */
class ParentBusinessUnitValidator extends ConstraintValidator
{
    /** @var OwnerTreeProviderInterface */
    private $ownerTreeProvider;

    /**
     * @param OwnerTreeProviderInterface $ownerTreeProvider
     */
    public function __construct(OwnerTreeProviderInterface $ownerTreeProvider)
    {
        $this->ownerTreeProvider = $ownerTreeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var BusinessUnit $value */
        $owner = $value->getOwner();
        if (null === $owner) {
            return;
        }

        if (in_array(
            $owner->getId(),
            $this->ownerTreeProvider->getTree()->getSubordinateBusinessUnitIds($value->getId()),
            true
        )) {
            $this->context->addViolation($constraint->message);
        }
    }
}
