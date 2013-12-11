<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;

class FormListener
{
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Add owner field to forms
     *
     * @param BeforeFormRenderEvent $event
     */
    public function addOwnerField(BeforeFormRenderEvent $event)
    {
        $environment = $event->getTwigEnvironment();
        $data = $event->getFormData();
        $form = $event->getForm();
        $label = false;

        if (is_object($form->vars['value'])) {
            $className = get_class($form->vars['value']);
            if (class_exists($className)) {
                $config = $this->configManager->getProvider('entity')->getConfig($className, 'owner');
                $label = $config->get('label');
            }

            $ownerField = $environment->render(
                "OroOrganizationBundle::owner.html.twig",
                array(
                    'form' => $form,
                    'label' => $label
                )
            );
        }

        /**
         * Setting owner field as last field in first data block
         */
        if (!empty($data['dataBlocks'])) {
            if (isset($data['dataBlocks'][0]['subblocks'])) {
                $data['dataBlocks'][0]['subblocks'][0]['data'][] = $ownerField;
            }
        }

        $event->setFormData($data);
    }
}
