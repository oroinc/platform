<?php

namespace Oro\Bundle\EmailBundle\Form\Configurator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration as Config;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This class adds configuration for email system configuration.
 */
class EmailConfigurationConfigurator
{
    /** @var SymmetricCrypterInterface */
    protected $encryptor;

    /**
     * @param SymmetricCrypterInterface $encryptor
     */
    public function __construct(SymmetricCrypterInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function configure(FormBuilderInterface $builder, $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $passwordKey = Config::getConfigKeyByName(
                Config::KEY_SMTP_SETTINGS_PASS,
                ConfigManager::SECTION_VIEW_SEPARATOR
            );

            if (!$event->getForm()->has($passwordKey)) {
                return;
            }

            $data = (array) $event->getData();

            if (empty($data[$passwordKey]['value'])) {
                $data[$passwordKey]['value'] = $event->getForm()->get($passwordKey)->getData()['value'];
            } else {
                $data[$passwordKey]['value'] = $this->encryptor->encryptData($data[$passwordKey]['value']);
            }

            $event->setData($data);
        }, 4);
    }
}
