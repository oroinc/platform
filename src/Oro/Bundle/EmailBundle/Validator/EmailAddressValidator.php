<?php

namespace Oro\Bundle\EmailBundle\Validator;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolation;

class EmailAddressValidator extends ConstraintValidator
{
    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /**
     * @param EmailAddressHelper $emailAddressHelper
     */
    public function __construct(EmailAddressHelper $emailAddressHelper)
    {
        $this->emailAddressHelper = $emailAddressHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
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
