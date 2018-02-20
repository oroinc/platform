<?php

namespace Oro\Bundle\EmailBundle\Form\Configurator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration as Config;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class EmailConfigurationConfigurator
{
    /** @var Mcrypt */
    protected $encryptor;

    /**
     * @param Mcrypt $encryptor
     */
    public function __construct(Mcrypt $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function configure(FormBuilderInterface $builder, $options)
    {
        $encryptor = $this->encryptor;
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($encryptor) {
            $data = (array) $event->getData();
            $passwordKey = Config::getConfigKeyByName(
                Config::KEY_SMTP_SETTINGS_PASS,
                ConfigManager::SECTION_VIEW_SEPARATOR
            );

            if (!isset($data[$passwordKey]['value'])
                || (
                    isset($data[$passwordKey]['value'])
                    && empty($data[$passwordKey]['value'])
                    && $event->getForm()->has($passwordKey)
                )
            ) {
                $data[$passwordKey]['value'] = $event->getForm()->get($passwordKey)->getData()['value'];
            } else {
                $data[$passwordKey]['value'] = $encryptor->encryptData($data[$passwordKey]['value']);
            }

            $event->setData($data);
        }, 4);
    }
}
