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
 * Validates that SMTP connection can be established with provided configuration parameters.
 */
class SmtpConnectionConfigurationValidator extends ConstraintValidator
{
    public function __construct(
        private readonly SmtpSettingsChecker $checker,
        private readonly SmtpSettingsFactory $smtpSettingsFactory,
        private readonly SymmetricCrypterInterface $encryptor
    ) {
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SmtpConnectionConfiguration) {
            throw new UnexpectedTypeException($constraint, SmtpConnectionConfiguration::class);
        }

        if (!\is_array($value)) {
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

    private function hasDataToValidateConnection(array $value): bool
    {
        return
            $this->getSmtpHost($value)
            || $this->getSmtpPort($value) > 0
            || $this->getSmtpUser($value);
    }

    private function hasDataToCheckConnection(array $value): bool
    {
        return
            $this->getSmtpHost($value)
            && $this->getSmtpPort($value) > 0
            && $this->getSmtpUser($value);
    }

    private function checkConnection(array $value): bool
    {
        return $this->checker->checkConnection($this->smtpSettingsFactory->createFromArray([
            $this->getSmtpHost($value),
            $this->getSmtpPort($value),
            $this->getSmtpEncryption($value),
            $this->getSmtpUser($value),
            $this->encryptor->decryptData($this->getSmtpPassword($value))
        ]));
    }

    private function getSmtpHost(array $value): ?string
    {
        return $this->getFieldValue($value, 'smtp_settings_host');
    }

    private function getSmtpPort(array $value): ?string
    {
        $port = $this->getFieldValue($value, 'smtp_settings_port');
        if (!$port || !is_numeric($port)) {
            return null;
        }

        return (int)$port;
    }

    private function getSmtpEncryption(array $value): ?string
    {
        return $this->getFieldValue($value, 'smtp_settings_encryption');
    }

    private function getSmtpUser(array $value): ?string
    {
        return $this->getFieldValue($value, 'smtp_settings_username');
    }

    private function getSmtpPassword(array $value): ?string
    {
        return $this->getFieldValue($value, 'smtp_settings_password');
    }

    private function getFieldValue(array $data, string $field): mixed
    {
        return $data['oro_email' . ConfigManager::SECTION_VIEW_SEPARATOR . $field][ConfigManager::VALUE_KEY] ?? null;
    }
}
