<?php

namespace Oro\Bundle\ConfigBundle\Form\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Type\ConfigFileType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Handles form pre-submission events for system configuration forms.
 *
 * This event subscriber processes form data before submission to handle the "use parent scope value"
 * checkbox behavior. When this checkbox is checked, it resets the field value to the parent scope's
 * value. For file fields, it sets an empty file state; for other field types, it retrieves and sets
 * the parent scope's configuration value. This ensures that configuration inheritance works correctly
 * when users opt to use parent scope values instead of defining scope-specific values.
 */
class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return array(
            FormEvents::PRE_SUBMIT => 'preSubmit'
        );
    }

    /**
     * Preset default values if default checkbox set
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        foreach ($data as $key => $val) {
            if (!empty($val['use_parent_scope_value'])) {
                $valueFileTypeClass = get_class($form->get($key)->get('value')->getConfig()->getType()->getInnerType());

                if (ConfigFileType::class === $valueFileTypeClass) {
                    $data[$key]['value'] = [
                        'file' => null,
                        'emptyFile' => true
                    ];
                } else {
                    $data[$key]['value'] = $this->configManager->get(
                        str_replace(
                            ConfigManager::SECTION_VIEW_SEPARATOR,
                            ConfigManager::SECTION_MODEL_SEPARATOR,
                            $key
                        ),
                        true
                    );
                }
            }
        }

        $event->setData($data);
    }
}
