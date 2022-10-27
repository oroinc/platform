<?php

namespace Oro\Bundle\LoggerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Divides string into parts and checks them for correspondence with the email address
 */
class EmailRecipientsListValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EmailRecipientsList) {
            throw new UnexpectedTypeException($constraint, EmailRecipientsList::class);
        }

        if (!$value) {
            return;
        }

        $validator = $this->context->getValidator();

        $invalid = [];
        foreach (explode(';', $value) as $emailAddress) {
            $emailAddress = trim($emailAddress);

            $violations = $validator->validate($emailAddress, new Email());
            if (count($violations)) {
                $invalid[] = $emailAddress;
            }
        }

        $invalid = array_unique($invalid);
        if ($invalid) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', implode(', ', $invalid))
                ->setPlural(count($invalid))
                ->addViolation();
        }
    }
}
