<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Type\ConfigCheckbox;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Definition of checkbox type for Microsoft Azure Application
 * IMAP synchronization enable/disable checkbox
 */
class MicrosoftSyncConfigCheckboxType extends ConfigCheckbox
{
    public const NAME = 'oro_config_microsoft_imap_sync_checkbox';

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            if (!$this->hasAllConfigValues()) {
                $parentForm = $form->getParent();
                $options = array_merge($form->getConfig()->getOptions(), [
                    'disabled' => true
                ]);
                $name = $form->getConfig()->getName();
                $parentForm->remove($name);
                $parentForm->add($name, ConfigCheckbox::class, $options);
            }
        });
    }

    /**
     * @return bool
     */
    private function hasAllConfigValues(): bool
    {
        foreach ($this->getMandatoryValues() as $mandatoryConfig) {
            if (!(bool)$this->configManager->get($mandatoryConfig)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    private function getMandatoryValues(): array
    {
        return [
            'oro_microsoft_integration.client_id',
            'oro_microsoft_integration.client_secret',
            'oro_microsoft_integration.tenant'
        ];
    }
}
