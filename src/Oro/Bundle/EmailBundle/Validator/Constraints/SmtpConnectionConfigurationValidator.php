<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration;
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

    /**
     * {@inheritdoc}
     */
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
        return $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_HOST);
    }

    private function getSmtpPort(array $value): ?string
    {
        $port = $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_PORT);
        if (!$port || !is_numeric($port)) {
            return null;
        }

        return (int)$port;
    }

    private function getSmtpEncryption(array $value): ?string
    {
        return $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_ENC);
    }

    private function getSmtpUser(array $value): ?string
    {
        return $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_USER);
    }

    private function getSmtpPassword(array $value): ?string
    {
        return $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_PASS);
    }

    private function getConfigValueByName(array $data, string $name): mixed
    {
        $configKey = Configuration::getConfigKeyByName($name, ConfigManager::SECTION_VIEW_SEPARATOR);

        return $data[$configKey][ConfigManager::VALUE_KEY] ?? null;
    }
}
