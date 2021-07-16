<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;

/**
 * Adds the owner field subblock to an entity edit page.
 */
class FormListener
{
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Add owner field to forms
     */
    public function addOwnerField(BeforeFormRenderEvent $event)
    {
        $form = $event->getForm();
        if (!isset($form['owner']) || $form['owner']->isRendered()) {
            return;
        }

        $label = false;

        if (is_object($form->vars['value'])) {
            $className = ClassUtils::getClass($form->vars['value']);
            $entityProvider = $this->configManager->getProvider('entity');
            if (class_exists($className) && $entityProvider->hasConfig($className, 'owner')) {
                $config = $entityProvider->getConfig($className, 'owner');
                $label = $config->get('label');
            }
        }

        // Setting owner field as first field in first data block.
        $data = $event->getFormData();
        if (!empty($data['dataBlocks'])) {
            reset($data['dataBlocks']);
            $firstBlockId = key($data['dataBlocks']);
            if (isset($data['dataBlocks'][$firstBlockId]['subblocks'])) {
                if (!isset($data['dataBlocks'][$firstBlockId]['subblocks'][0])) {
                    $data['dataBlocks'][$firstBlockId]['subblocks'][0] = ['data' => []];
                }

                $ownerField = $event->getTwigEnvironment()
                    ->render('@OroOrganization/owner.html.twig', ['form'  => $form, 'label' => $label]);
                array_unshift($data['dataBlocks'][$firstBlockId]['subblocks'][0]['data'], $ownerField);
            }
        }

        $event->setFormData($data);
    }
}
