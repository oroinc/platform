<?php

namespace Oro\Bundle\ConfigBundle\Form\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Type\ConfigFileType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SUBMIT => 'preSubmit'
        );
    }

    /**
     * Preset default values if default checkbox set
     *
     * @param FormEvent $event
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
