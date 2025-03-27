<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator is used to check if access to an associated entity is granted.
 */
class AccessGrantedValidator extends ConstraintValidator
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof AccessGranted) {
            throw new UnexpectedTypeException($constraint, AccessGranted::class);
        }

        if (null === $value) {
            return;
        }

        if (!\is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        $subject = $value;
        try {
            if ($this->doctrineHelper->isNewEntity($value)) {
                $subject = ObjectIdentityHelper::encodeIdentityString(
                    EntityAclExtension::NAME,
                    $this->doctrineHelper->getClass($value)
                );
            }
        } catch (NotManageableEntityException) {
            // not manageable entities should be checked as usual
        }

        if (!$this->authorizationChecker->isGranted($constraint->permission, $subject)) {
            $this->context->addViolation(
                $constraint->message,
                ['{{ permission }}' => $constraint->permission]
            );
        }
    }
}
