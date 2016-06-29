<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class AccessGrantedValidator extends ConstraintValidator
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
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

        if (!$this->securityFacade->isGranted('VIEW', $value)) {
            $this->context->addViolation($constraint->message);
        }
    }
}
