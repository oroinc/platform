<?php

namespace Oro\Bundle\ImapBundle\Validator\Constraints;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that SMTP connection can be established with parameters from UserEmailOrigin.
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

        if (!$value instanceof UserEmailOrigin) {
            return;
        }

        if (!$this->hasDataToValidateConnection($value)) {
            return;
        }

        if (!$this->hasDataToCheckConnection($value) || !$this->checkConnection($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    private function hasDataToValidateConnection(UserEmailOrigin $value): bool
    {
        return
            $value->getSmtpHost()
            || $value->getSmtpPort() > 0;
    }

    private function hasDataToCheckConnection(UserEmailOrigin $value): bool
    {
        return $value->isSmtpConfigured();
    }

    private function checkConnection(UserEmailOrigin $value): bool
    {
        return $this->checker->checkConnection($this->smtpSettingsFactory->createFromUserEmailOrigin($value));
    }
}
