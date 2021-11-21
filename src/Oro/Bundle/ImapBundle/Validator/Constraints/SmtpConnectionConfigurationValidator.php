<?php

namespace Oro\Bundle\ImapBundle\Validator\Constraints;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that an SMTP connection can be established with SMTP parameters from UserEmailOrigin
 */
class SmtpConnectionConfigurationValidator extends ConstraintValidator
{
    private SmtpSettingsChecker $checker;
    private SmtpSettingsFactory $smtpSettingsFactory;

    public function __construct(SmtpSettingsChecker $checker, SmtpSettingsFactory $smtpSettingsFactory)
    {
        $this->checker = $checker;
        $this->smtpSettingsFactory = $smtpSettingsFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SmtpConnectionConfiguration) {
            throw new UnexpectedTypeException($constraint, SmtpConnectionConfiguration::class);
        }

        if ($value instanceof UserEmailOrigin && $value->isSmtpConfigured() === true) {
            $smtpSettings = $this->smtpSettingsFactory->createFromUserEmailOrigin($value);
            if (!$this->checker->checkConnection($smtpSettings)) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
}
