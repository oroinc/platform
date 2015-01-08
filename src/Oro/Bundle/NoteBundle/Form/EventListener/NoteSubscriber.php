<?php

namespace Oro\Bundle\NoteBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class NoteSubscriber implements EventSubscriberInterface
{
    /** @var  ConfigProvider */
    protected $extendConfigProvider;

    /** @var  ConfigProvider */
    protected $entityConfigProvider;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->entityConfigProvider = $configManager->getProvider('entity');
        $this->extendConfigProvider = $configManager->getProvider('extend');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
        );
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();

        $entityClass = $form->getConfig()->getDataClass();

        /** @var FieldConfigId[] $fieldConfigIds */
        $fieldConfigIds = $this->extendConfigProvider->getIds($entityClass);
        foreach ($fieldConfigIds as $fieldConfigId) {
            if ($fieldConfigId->getFieldType() === 'manyToOne') {
                $fieldEntityConfig = $this->entityConfigProvider->getConfigById($fieldConfigId);
                $fieldExtendConfig = $this->extendConfigProvider->getConfigById($fieldConfigId);

                if (!$fieldExtendConfig->is('state', ExtendScope::STATE_ACTIVE)) {
                    continue;
                }

                $form->add(
                    $fieldConfigId->getFieldName(),
                    'entity',
                    [
                        'required' => false,
                        'class'    => $fieldExtendConfig->get('target_entity'),
                        'property' => $fieldExtendConfig->get('target_field'),
                        'label'    => $fieldEntityConfig->get('label')
                    ]
                );
            }
        }
    }
}
