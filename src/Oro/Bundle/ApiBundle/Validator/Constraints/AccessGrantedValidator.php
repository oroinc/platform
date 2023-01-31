<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator is used to check if an access to an associated entity is granted.
 */
class AccessGrantedValidator extends ConstraintValidator
{
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
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

        if (!$this->authorizationChecker->isGranted($constraint->permission, $value)) {
            $this->context->addViolation(
                $constraint->message,
                ['{{ permission }}' => $constraint->permission]
            );
        }
    }
}
