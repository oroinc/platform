<?php

namespace Oro\Bundle\EntityExtendBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ConfigTypeSubscriber implements EventSubscriberInterface
{
    /** @var ConfigManager $configManager */
    protected $configManager;

    /** @var callable */
    protected $schemaUpdateRequired;

    /**
     * @param ConfigManager $configManager
     * @param callable $schemaUpdateRequired (oldValue, newValue) -> bool
     */
    public function __construct(ConfigManager $configManager, callable $schemaUpdateRequired)
    {
        $this->configManager = $configManager;
        $this->schemaUpdateRequired = $schemaUpdateRequired;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    /**
     * POST_SUBMIT event handler
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form    = $event->getForm();
        $options = $form->getConfig()->getOptions();
        /** @var ConfigIdInterface $configId */
        $configId = $options['config_id'];

        if (!$form->isValid()) {
            return;
        }

        // change the entity state to "Requires update" if the attribute has "require_schema_update" option
        // and the value of the attribute was changed
        $configProvider = $this->configManager->getProvider($configId->getScope());
        if ($configProvider->getPropertyConfig()->isSchemaUpdateRequired($form->getName(), $configId)) {
            $newVal = $form->getData();
            $oldVal = $this->configManager->getConfig($configId)->get($form->getName());
            if (call_user_func($this->schemaUpdateRequired, $newVal, $oldVal)) {
                $extendConfigProvider = $this->configManager->getProvider('extend');
                $extendConfig         = $extendConfigProvider->getConfig($configId->getClassName());

                if ($configId instanceof EntityConfigId) {
                    $pendingChanges = $extendConfig->get('pending_changes', false, []);
                    $pendingChanges[$configId->getScope()][$form->getName()] = [
                        $oldVal,
                        $newVal,
                    ];
                    $extendConfig->set('pending_changes', $pendingChanges);
                }

                if ($extendConfig->is('state', ExtendScope::STATE_ACTIVE)) {
                    $extendConfig->set('state', ExtendScope::STATE_UPDATE);
                }

                $this->configManager->persist($extendConfig);
            }
        }
    }
}
