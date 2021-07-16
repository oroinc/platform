<?php

namespace Oro\Bundle\EmailBundle\Form\Configurator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration as Config;
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
    /** @var SymmetricCrypterInterface */
    protected $encryptor;

    /** @var ValidatorInterface */
    private $validator;

    public function __construct(SymmetricCrypterInterface $encryptor, ValidatorInterface $validator)
    {
        $this->encryptor = $encryptor;
        $this->validator = $validator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function configure(FormBuilderInterface $builder, $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $passwordKey = $this->getConfigKey(Config::KEY_SMTP_SETTINGS_PASS);

            if (!$event->getForm()->has($passwordKey)) {
                return;
            }

            $data = (array) $event->getData();

            if (empty($data[$passwordKey]['value'])) {
                $passwordData = $event->getForm()->get($passwordKey)->getData();
                $data[$passwordKey]['value'] = $passwordData['value'] ?? null;
            } else {
                $data[$passwordKey]['value'] = $this->encryptor->encryptData($data[$passwordKey]['value']);
            }

            $event->setData($data);
        }, 4);

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit'], -1);
    }

    /**
     * Validate the form with SmtpConnectionConfiguration constraint
     */
    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (!$this->isSmtpFieldsExist($data)) {
            return;
        }
        if ($this->getParentScopeValue($data, Config::KEY_SMTP_SETTINGS_HOST)
            && $this->getParentScopeValue($data, Config::KEY_SMTP_SETTINGS_PORT)
            && $this->getParentScopeValue($data, Config::KEY_SMTP_SETTINGS_ENC)
            && $this->getParentScopeValue($data, Config::KEY_SMTP_SETTINGS_USER)
            && $this->getParentScopeValue($data, Config::KEY_SMTP_SETTINGS_PASS)
        ) {
            return;
        }

        $errors = $this->validator->validate($data, new SmtpConnectionConfiguration());
        $form = $event->getForm();
        foreach ($errors as $error) {
            $form->addError(new FormError($error->getMessage()));
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getConfigKey($name)
    {
        return Config::getConfigKeyByName($name, ConfigManager::SECTION_VIEW_SEPARATOR);
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    private function isSmtpFieldsExist(array $data)
    {
        return $this->isFieldExist($data, Config::KEY_SMTP_SETTINGS_HOST)
            && $this->isFieldExist($data, Config::KEY_SMTP_SETTINGS_PORT)
            && $this->isFieldExist($data, Config::KEY_SMTP_SETTINGS_ENC)
            && $this->isFieldExist($data, Config::KEY_SMTP_SETTINGS_USER)
            && $this->isFieldExist($data, Config::KEY_SMTP_SETTINGS_PASS);
    }

    /**
     * @param array $data
     * @param string $key
     *
     * @return bool
     */
    private function isFieldExist(array $data, $key)
    {
        return isset($data[$this->getConfigKey($key)]);
    }

    /**
     * @param array $data
     * @param string $key
     *
     * @return bool
     */
    private function getParentScopeValue(array $data, $key)
    {
        return $data[$this->getConfigKey($key)][ConfigManager::USE_PARENT_SCOPE_VALUE_KEY];
    }
}
