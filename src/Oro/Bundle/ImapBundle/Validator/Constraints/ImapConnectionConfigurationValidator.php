<?php

namespace Oro\Bundle\ImapBundle\Validator\Constraints;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapSettingsChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that an IMAP connection can be established with parameters from UserEmailOrigin
 */
class ImapConnectionConfigurationValidator extends ConstraintValidator
{
    private ImapSettingsChecker $checker;

    public function __construct(ImapSettingsChecker $checker)
    {
        $this->checker = $checker;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ImapConnectionConfiguration) {
            throw new UnexpectedTypeException($constraint, ImapConnectionConfiguration::class);
        }

        if (!$value instanceof UserEmailOrigin
            || false === $value->isImapConfigured()
        ) {
            return;
        }

        $result = $this->checker->checkConnection($value);
        if (false === $result) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
