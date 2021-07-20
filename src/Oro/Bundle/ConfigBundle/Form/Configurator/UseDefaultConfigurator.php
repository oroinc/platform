<?php

namespace Oro\Bundle\ConfigBundle\Form\Configurator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Config form configurator.
 * Hide Use Default option for given option and scope
 */
class UseDefaultConfigurator
{
    protected const USE_PARENT_SCOPE_VALUE = 'use_parent_scope_value';

    /**
     * @var ConfigHandler
     */
    private $configHandler;

    /**
     * @var array
     * [ scope => [field, ...], ...]
     */
    private $doNotShow = [];

    public function __construct(ConfigHandler $configHandler)
    {
        $this->configHandler = $configHandler;
    }

    public function disableUseDefaultFor(string $scope, string $section, string $option)
    {
        $this->doNotShow[$scope][] = $section . ConfigManager::SECTION_VIEW_SEPARATOR . $option;
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                if ($event->getData() === null) {
                    return;
                }

                $configManager = $this->configHandler->getConfigManager();
                $currentScope = $configManager->getScopeEntityName();
                if (array_key_exists($currentScope, $this->doNotShow)) {
                    $form = $event->getForm();
                    foreach ($this->doNotShow[$currentScope] as $field) {
                        $this->hideUseParentScopeCheckbox($form->get($field));
                    }
                }
            }
        );
    }

    private function hideUseParentScopeCheckbox(FormInterface $form): void
    {
        $options = $form->get(self::USE_PARENT_SCOPE_VALUE)
            ->getConfig()
            ->getOptions();

        unset($options['value'], $options['false_values']);

        $form->add(self::USE_PARENT_SCOPE_VALUE, HiddenType::class, $options);
    }
}
