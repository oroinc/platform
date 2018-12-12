<?php

namespace Oro\Bundle\LocaleBundle\Form\Configurator;

use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
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

                $configManager = $this->configHandler->getConfigManager();
                if ($configManager->getScopeEntityName() !== GlobalScopeManager::SCOPE_NAME) {
                    return;
                }

                $form = $event->getForm();

                $this->hideUseParentScopeCheckbox($form->get('oro_locale___default_localization'));
                $this->hideUseParentScopeCheckbox($form->get('oro_locale___enabled_localizations'));
            }
        );
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
