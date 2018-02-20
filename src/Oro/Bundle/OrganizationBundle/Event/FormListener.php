<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Doctrine\Common\Util\ClassUtils;
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
        $entityProvider = $this->configManager->getProvider('entity');

        if (is_object($form->vars['value'])) {
            $className = ClassUtils::getClass($form->vars['value']);
            if (class_exists($className)
                && $entityProvider->hasConfig($className, 'owner')
            ) {
                $config = $entityProvider->getConfig($className, 'owner');
                $label  = $config->get('label');
            }
        }

        $ownerField = $environment->render(
            "OroOrganizationBundle::owner.html.twig",
            array(
                'form'  => $form,
                'label' => $label
            )
        );

        /**
         * Setting owner field as first field in first data block
         */
        if (!empty($data['dataBlocks'])) {
            reset($data['dataBlocks']);
            $firstBlockId = key($data['dataBlocks']);
            if (isset($data['dataBlocks'][$firstBlockId]['subblocks'])) {
                if (!isset($data['dataBlocks'][$firstBlockId]['subblocks'][0])) {
                    $data['dataBlocks'][$firstBlockId]['subblocks'][0] = ['data' => []];
                }
                array_unshift($data['dataBlocks'][$firstBlockId]['subblocks'][0]['data'], $ownerField);
            }
        }

        $event->setFormData($data);
    }
}
