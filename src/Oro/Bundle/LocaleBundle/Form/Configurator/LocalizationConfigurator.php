<?php

namespace Oro\Bundle\LocaleBundle\Form\Configurator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration as Config;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationSelectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Localization form configurator
 */
class LocalizationConfigurator
{
    /** @var ConfigHandler */
    private $configHandler;

    /**
     * @param ConfigHandler $configHandler
     */
    public function __construct(ConfigHandler $configHandler)
    {
        $this->configHandler = $configHandler;
    }

    /**
     * @param FormBuilderInterface $builder
     */
    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                if ($event->getData() === null) {
                    return;
                }

                $form = $event->getForm();
                $configManager = $this->configHandler->getConfigManager();

                $this->setEnabledLocalizations($form, $configManager);

                if ($configManager->getScopeEntityName() !== GlobalScopeManager::SCOPE_NAME) {
                    return;
                }

                $this->hideUseParentScopeCheckbox($form->get(Config::getFieldKeyByName(Config::DEFAULT_LOCALIZATION)));
                $this->hideUseParentScopeCheckbox($form->get(Config::getFieldKeyByName(Config::ENABLED_LOCALIZATIONS)));
            }
        );
    }

    /**
     * @param FormInterface $form
     * @param ConfigManager $configManager
     */
    private function setEnabledLocalizations(FormInterface $form, ConfigManager $configManager): void
    {
        $form = $form->get(Config::getFieldKeyByName(Config::DEFAULT_LOCALIZATION));

        $options = $form->get('value')
            ->getConfig()
            ->getOptions();

        $options[Config::ENABLED_LOCALIZATIONS] = $configManager->get(
            Config::getConfigKeyByName(Config::ENABLED_LOCALIZATIONS)
        );

        $form->add('value', LocalizationSelectionType::class, $options);
    }

    /**
     * @param FormInterface $form
     */
    private function hideUseParentScopeCheckbox(FormInterface $form): void
    {
        $options = $form->get('use_parent_scope_value')
            ->getConfig()
            ->getOptions();

        unset($options['value']);

        $form->add('use_parent_scope_value', HiddenType::class, $options);
    }
}
