<?php

namespace Oro\Bundle\ConfigBundle\Form\Handler;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles form submission and processing for system configuration.
 *
 * This handler manages the complete lifecycle of configuration form processing, including
 * loading initial settings data, handling POST/PUT requests, validating form data, and
 * persisting changes to the configuration manager. It supports custom post-processing through
 * callable handlers attached to the form configuration, allowing for additional logic to be
 * executed after successful configuration updates.
 */
class ConfigHandler
{
    use RequestHandlerTrait;

    /**
     * @var ConfigManager
     */
    protected $manager;

    public function __construct(ConfigManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param ConfigManager $manager
     *
     * @return $this
     */
    public function setConfigManager(ConfigManager $manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->manager;
    }

    /**
     * Process form
     *
     * @param FormInterface $form
     *
     * @param Request $request
     * @return bool True on successful processing, false otherwise
     */
    public function process(FormInterface $form, Request $request)
    {
        $settingsData = $this->manager->getSettingsByForm($form);
        $form->setData($settingsData);

        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            $this->submitPostPutRequest($form, $request);
            if ($form->isValid()) {
                $changeSet = $this->manager->save($form->getData());
                $handler = $form->getConfig()->getAttribute('handler');
                if (null !== $handler && is_callable($handler)) {
                    call_user_func($handler, $this->manager, $changeSet, $form);
                }

                return true;
            }
        }

        return false;
    }
}
