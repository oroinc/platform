<?php

namespace Oro\Bundle\ImapBundle\Validator\Constraints;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapSettingsChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that IMAP connection can be established with parameters from UserEmailOrigin.
 */
class ImapConnectionConfigurationValidator extends ConstraintValidator
{
    private ImapSettingsChecker $checker;

    public function __construct(ImapSettingsChecker $checker)
    {
        $this->checker = $checker;
    }

    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ImapConnectionConfiguration) {
            throw new UnexpectedTypeException($constraint, ImapConnectionConfiguration::class);
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
            $value->getImapHost()
            || $value->getImapPort() > 0;
    }

    private function hasDataToCheckConnection(UserEmailOrigin $value): bool
    {
        return $value->isImapConfigured();
    }

    private function checkConnection(UserEmailOrigin $value): bool
    {
        return $this->checker->checkConnection($value);
    }
}
