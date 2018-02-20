<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration as Config;
use Oro\Bundle\EmailBundle\Event\SmtpSettingsSaved;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

class EmailConfigurationHandler
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var ConfigManager */
    protected $configManager;

    /** @var ConfigChangeSet $changeSet */
    protected $changeSet;

    /** @var FormInterface $form */
    protected $form;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param ConfigManager   $manager
     * @param ConfigChangeSet $changeSet
     * @param FormInterface   $form
     */
    public function process(ConfigManager $manager, ConfigChangeSet $changeSet, FormInterface $form)
    {
        $this->configManager = $manager;
        $this->changeSet = $changeSet;
        $this->form = $form;

        $this->processSmtpSettings();
    }


    protected function processSmtpSettings()
    {
        if (!$this->changeSet->isChanged($this->getConfigKey(Config::KEY_SMTP_SETTINGS_HOST))
            && !$this->changeSet->isChanged($this->getConfigKey(Config::KEY_SMTP_SETTINGS_PORT))
            && !$this->changeSet->isChanged($this->getConfigKey(Config::KEY_SMTP_SETTINGS_ENC))
            && !$this->changeSet->isChanged($this->getConfigKey(Config::KEY_SMTP_SETTINGS_USER))
            && !$this->changeSet->isChanged($this->getConfigKey(Config::KEY_SMTP_SETTINGS_PASS))
        ) {
            return;
        }

        if ($this->dispatcher->hasListeners(SmtpSettingsSaved::NAME)) {
            $smtpSettings = new SmtpSettings(
                $this->getFormFieldValue(Config::KEY_SMTP_SETTINGS_HOST),
                $this->getFormFieldValue(Config::KEY_SMTP_SETTINGS_PORT),
                $this->getFormFieldValue(Config::KEY_SMTP_SETTINGS_ENC),
                $this->getFormFieldValue(Config::KEY_SMTP_SETTINGS_USER),
                $this->getFormFieldValue(Config::KEY_SMTP_SETTINGS_PASS)
            );

            $this->dispatcher->dispatch(SmtpSettingsSaved::NAME, new SmtpSettingsSaved($smtpSettings));
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getConfigKey($name)
    {
        return Config::getConfigKeyByName($name);
    }

    /**
     * @param string $field
     *
     * @return mixed
     */
    protected function getFormFieldValue($field)
    {
        $fieldName = Config::getConfigKeyByName($field, ConfigManager::SECTION_VIEW_SEPARATOR);

        if (!$this->form->has($fieldName)) {
            return null;
        }

        return $this->form->get($fieldName)->getData()['value'];
    }
}
