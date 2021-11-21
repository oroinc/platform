<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that an email has at least one recipient.
 */
class EmailRecipientsValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EmailRecipients) {
            throw new UnexpectedTypeException($constraint, EmailRecipients::class);
        }
        if (!$value instanceof Email) {
            throw new UnexpectedTypeException($value, Email::class);
        }

        if (!$value->getTo() && !$value->getCc() && !$value->getBcc()) {
            $this->context->addViolation($constraint->message);
        }
    }
}
