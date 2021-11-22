<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a string represents a valid email address.
 */
class EmailAddressValidator extends ConstraintValidator
{
    private EmailAddressHelper $emailAddressHelper;

    public function __construct(EmailAddressHelper $emailAddressHelper)
    {
        $this->emailAddressHelper = $emailAddressHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EmailAddress) {
            throw new UnexpectedTypeException($constraint, EmailAddress::class);
        }

        if (is_scalar($value)) {
            $value = [$value];
        }

        $emailValidator = new EmailValidator();
        $emailValidator->initialize($this->context);

        foreach ($value as $fullEmailAddress) {
            $email = $this->emailAddressHelper->extractPureEmailAddress($fullEmailAddress);
            if (!$email) {
                continue;
            }
            $emailValidator->validate($email, $constraint);
            if ($this->context->getViolations()->count()) {
                foreach ($this->context->getViolations() as $violation) {
                    /** @var ConstraintViolation $violation */
                    if ($violation->getPropertyPath() == $this->context->getPropertyPath()) {
                        return;
                    }
                }
            }
        }
    }
}
