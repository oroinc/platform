<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * The validator for AccessGranted constraint.
 */
class AccessGrantedValidator extends ConstraintValidator
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof AccessGranted) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\AccessGranted');
        }

        if (null === $value) {
            return;
        }

        if (!$this->authorizationChecker->isGranted($constraint->permission, $value)) {
            $this->context->addViolation(
                $constraint->message,
                ['{{ permission }}' => $constraint->permission]
            );
        }
    }
}
