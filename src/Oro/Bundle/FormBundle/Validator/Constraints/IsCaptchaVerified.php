<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks for CAPTCHA token validity
 */
class IsCaptchaVerified extends Constraint
{
    public string $message = 'oro.form.captcha.captcha_not_verified';

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string|array
    {
        return Constraint::PROPERTY_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return IsCaptchaVerifiedValidator::class;
    }
}
