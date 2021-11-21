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

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SmtpConnectionConfiguration) {
            throw new UnexpectedTypeException($constraint, SmtpConnectionConfiguration::class);
        }

        if (is_array($value)) {
            $smtpSettings = $this->smtpSettingsFactory->createFromArray(
                [
                    $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_HOST),
                    $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_PORT),
                    $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_ENC),
                    $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_USER),
                    $this->encryptor->decryptData(
                        $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_PASS)
                    )
                ]
            );

            $result = $this->checker->checkConnection($smtpSettings);

            if (!$result) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }

    private function getConfigValueByName(array $data, string $name): mixed
    {
        $configKey = Configuration::getConfigKeyByName($name, ConfigManager::SECTION_VIEW_SEPARATOR);

        return $data[$configKey][ConfigManager::VALUE_KEY] ?? null;
    }
}
