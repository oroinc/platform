<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
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
    private SymmetricCrypterInterface $encryptor;

    public function __construct(
        SmtpSettingsChecker $checker,
        SmtpSettingsFactory $smtpSettingsFactory,
        SymmetricCrypterInterface $encryptor
    ) {
        $this->checker = $checker;
        $this->smtpSettingsFactory = $smtpSettingsFactory;
        $this->encryptor = $encryptor;
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SmtpConnectionConfiguration) {
            throw new UnexpectedTypeException($constraint, SmtpConnectionConfiguration::class);
        }

        if (\is_array($value)) {
            $result = $this->checker->checkConnection($this->smtpSettingsFactory->createFromArray([
                $this->getFieldValue($value, 'smtp_settings_host'),
                $this->getFieldValue($value, 'smtp_settings_port'),
                $this->getFieldValue($value, 'smtp_settings_encryption'),
                $this->getFieldValue($value, 'smtp_settings_username'),
                $this->encryptor->decryptData($this->getFieldValue($value, 'smtp_settings_password'))
            ]));
            if (!$result) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }

    private function getFieldValue(array $data, string $field): mixed
    {
        return $data['oro_email' . ConfigManager::SECTION_VIEW_SEPARATOR . $field][ConfigManager::VALUE_KEY] ?? null;
    }
}
