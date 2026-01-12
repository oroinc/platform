<?php

namespace Oro\Bundle\NoteBundle\Form\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Form event subscriber that dynamically adds entity relation fields to note forms.
 *
 * This subscriber listens to form pre-set-data events and inspects the note entity's
 * extended configuration to discover manyToOne relationships. For each active manyToOne
 * field, it dynamically adds an {@see EntityType} form field to the form, allowing users to
 * select related entities. This enables flexible note forms that adapt to custom entity
 * relationships without requiring explicit form configuration.
 */
class NoteSubscriber implements EventSubscriberInterface
{
    /** @var  ConfigProvider */
    protected $extendConfigProvider;

    /** @var  ConfigProvider */
    protected $entityConfigProvider;

    public function __construct(ConfigManager $configManager)
    {
        $this->entityConfigProvider = $configManager->getProvider('entity');
        $this->extendConfigProvider = $configManager->getProvider('extend');
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
        );
    }

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
                    EntityType::class,
                    [
                        'required' => false,
                        'class' => $fieldExtendConfig->get('target_entity'),
                        'choice_label' => $fieldExtendConfig->get('target_field'),
                        'label' => $fieldEntityConfig->get('label')
                    ]
                );
            }
        }
    }
}
