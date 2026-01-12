<?php

namespace Oro\Bundle\EmailBundle\Form\Configurator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Validator\Constraints\SmtpConnectionConfiguration;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * This class adds configuration for email system configuration.
 */
class EmailConfigurationConfigurator
{
    private SymmetricCrypterInterface $encryptor;
    private ValidatorInterface $validator;

    public function __construct(SymmetricCrypterInterface $encryptor, ValidatorInterface $validator)
    {
        $this->encryptor = $encryptor;
        $this->validator = $validator;
    }

    public function configure(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $passwordFieldName = $this->getFieldName('smtp_settings_password');
            if (!$event->getForm()->has($passwordFieldName)) {
                return;
            }

            $data = (array)$event->getData();

            if (empty($data[$passwordFieldName][ConfigManager::VALUE_KEY])) {
                $passwordData = $event->getForm()->get($passwordFieldName)->getData();
                $data[$passwordFieldName][ConfigManager::VALUE_KEY] = $passwordData[ConfigManager::VALUE_KEY] ?? null;
            } else {
                $data[$passwordFieldName][ConfigManager::VALUE_KEY] = $this->encryptor->encryptData(
                    $data[$passwordFieldName][ConfigManager::VALUE_KEY]
                );
            }

            $event->setData($data);
        }, 4);

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit'], -1);
    }

    /**
     * Validates the form with {@see SmtpConnectionConfiguration} constraint.
     */
    public function postSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        if (!$this->isSmtpFieldsExist($data)) {
            return;
        }
        if (
            $this->getParentScopeValue($data, 'smtp_settings_host')
            && $this->getParentScopeValue($data, 'smtp_settings_port')
            && $this->getParentScopeValue($data, 'smtp_settings_encryption')
            && $this->getParentScopeValue($data, 'smtp_settings_username')
            && $this->getParentScopeValue($data, 'smtp_settings_password')
        ) {
            return;
        }

        $errors = $this->validator->validate($data, new SmtpConnectionConfiguration());
        $form = $event->getForm();
        foreach ($errors as $error) {
            $form->addError(new FormError($error->getMessage()));
        }
    }

    private function getFieldName(string $field): string
    {
        return 'oro_email' . ConfigManager::SECTION_VIEW_SEPARATOR . $field;
    }

    private function isSmtpFieldsExist(array $data): bool
    {
        return
            $this->isFieldExist($data, 'smtp_settings_host')
            && $this->isFieldExist($data, 'smtp_settings_port')
            && $this->isFieldExist($data, 'smtp_settings_encryption')
            && $this->isFieldExist($data, 'smtp_settings_username')
            && $this->isFieldExist($data, 'smtp_settings_password');
    }

    private function isFieldExist(array $data, string $field): bool
    {
        return isset($data[$this->getFieldName($field)]);
    }

    private function getParentScopeValue(array $data, string $field): bool
    {
        return $data[$this->getFieldName($field)][ConfigManager::USE_PARENT_SCOPE_VALUE_KEY];
    }
}
