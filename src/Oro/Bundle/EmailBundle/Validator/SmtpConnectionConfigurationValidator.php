<?php

namespace Oro\Bundle\EmailBundle\Validator;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that an SMTP connection can be established with SMTP parameters from UserEmailOrigin
 */
class SmtpConnectionConfigurationValidator extends ConstraintValidator
{
    /** @var SmtpSettingsChecker */
    private $checker;

    /** @var SmtpSettingsFactory */
    private $smtpSettingsFactory;

    public function __construct(SmtpSettingsChecker $checker, SmtpSettingsFactory $smtpSettingsFactory)
    {
        $this->checker = $checker;
        $this->smtpSettingsFactory = $smtpSettingsFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (($value instanceof UserEmailOrigin
            && $value->isSmtpConfigured() === true)
            || is_array($value)
        ) {
            $smtpSettings = $this->smtpSettingsFactory->create($value);
            $result = $this->checker->checkConnection($smtpSettings);

            if ('' !== $result) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
}
