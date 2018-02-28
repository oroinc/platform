<?php

namespace Oro\Bundle\EmailBundle\Validator;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailRecipients;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EmailRecipientsValidator extends ConstraintValidator
{
    /**
     * @param Email $value
     * @param Constraint|EmailRecipients $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value->getTo() && !$value->getCc() && !$value->getBcc()) {
            $this->context->addViolation($constraint->message);
        }
    }
}
