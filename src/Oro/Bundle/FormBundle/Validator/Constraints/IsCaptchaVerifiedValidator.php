<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Oro\Bundle\FormBundle\Captcha\CaptchaServiceRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks for CAPTCHA token validity
 */
class IsCaptchaVerifiedValidator extends ConstraintValidator
{
    public function __construct(
        private CaptchaServiceRegistry $captchaServiceRegistry
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->captchaServiceRegistry->getCaptchaService()->isVerified($value)) {
            return;
        }

        $this->context->addViolation($constraint->message);
    }
}
